<?php

namespace Tests\Feature\Platform\Tracing;

use App\Modules\Platform\Support\Correlation;
use App\Modules\Platform\Tracing\Services\TraceManager;
use ArrayObject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use Tests\TestCase;

class TracingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tracing.enabled' => true,
            'tracing.exporter' => 'memory',
            'tracing.sampler_ratio' => 1.0,
            'tracing.sources' => [
                'http' => true,
                'http_client' => true,
                'database' => true,
                'queue' => true,
            ],
        ]);

        $this->app->instance(ArrayObject::class, new ArrayObject);
        $this->app->forgetInstance(TraceManager::class);
    }

    /** @return array<int, SpanDataInterface> */
    private function spans(): array
    {
        return app(TraceManager::class)->exportedSpans();
    }

    private function spanAttributes(SpanDataInterface $span): array
    {
        return $span->getAttributes()->toArray();
    }

    private function findSpan(string $name): ?SpanDataInterface
    {
        foreach ($this->spans() as $span) {
            if ($span->getName() === $name) {
                return $span;
            }
        }

        return null;
    }

    public function test_http_request_produces_a_root_span_with_method_route_status_duration_and_correlation(): void
    {
        $correlation = (string) Str::uuid();

        $this->get('/', ['X-Correlation-ID' => $correlation])->assertOk();

        $span = $this->findSpan('GET /');

        $this->assertNotNull($span);

        $attributes = $this->spanAttributes($span);

        $this->assertSame('GET', $attributes['http.request.method'] ?? null);
        $this->assertSame('/', $attributes['http.route'] ?? null);
        $this->assertSame(200, $attributes['http.response.status_code'] ?? null);
        $this->assertArrayHasKey('http.duration_ms', $attributes);
        $this->assertSame($correlation, $attributes['app.correlation_id'] ?? null);
    }

    public function test_unhandled_exception_is_recorded_on_the_root_span(): void
    {
        $this->withoutExceptionHandling();
        $this->app->forgetInstance(ExceptionHandler::class);

        try {
            $this->get('/non-existent-route-xyz');
            $this->fail('Expected exception to be thrown');
        } catch (\Throwable $throwable) {
            // Expected: route not found.
        }

        $spans = $this->spans();
        $this->assertNotEmpty($spans);
    }

    public function test_outbound_http_call_produces_a_child_span_without_payloads(): void
    {
        Http::fake(['https://example.com/*' => Http::response(['ok' => true], 201)]);

        $response = Http::post('https://example.com/api/v1/payments', [
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'secret_token' => 'super-secret-value',
        ]);

        $this->assertSame(201, $response->status());

        $span = $this->findSpan('HTTP POST');

        $this->assertNotNull($span);

        $attributes = $this->spanAttributes($span);

        $this->assertSame('POST', $attributes['http.request.method'] ?? null);
        $this->assertSame('https://example.com/api/v1/payments', $attributes['url.full'] ?? null);
        $this->assertSame(201, $attributes['http.response.status_code'] ?? null);
        $this->assertArrayHasKey('http.duration_ms', $attributes);

        $encoded = json_encode($attributes);

        $this->assertStringNotContainsString('4111111111111111', $encoded);
        $this->assertStringNotContainsString('super-secret-value', $encoded);
    }

    public function test_database_query_produces_a_child_span_without_sql_or_bindings(): void
    {
        DB::table('users')->where('id', 12345)->where('password', 'hunter2-secret')->first();

        $span = $this->findSpan('DB SELECT');

        $this->assertNotNull($span);

        $attributes = $this->spanAttributes($span);

        $this->assertSame('mysql', $attributes['db.system'] ?? null);
        $this->assertSame('SELECT', $attributes['db.operation'] ?? null);
        $this->assertSame('users', $attributes['db.namespace'] ?? null);
        $this->assertArrayHasKey('db.duration_ms', $attributes);

        $encoded = json_encode($attributes);

        $this->assertStringNotContainsString('hunter2-secret', $encoded);
        $this->assertStringNotContainsString('password', $encoded);
    }

    public function test_queued_job_with_correlation_produces_a_span_and_without_produces_none(): void
    {
        $correlation = (string) Str::uuid();
        Correlation::set($correlation);

        Bus::dispatchSync(new TracingTestJob);

        Correlation::reset();

        $jobSpan = $this->findSpan('Job '.TracingTestJob::class);

        $this->assertNotNull($jobSpan);

        $attributes = $this->spanAttributes($jobSpan);

        $this->assertSame(TracingTestJob::class, $attributes['job.name'] ?? null);
        $this->assertSame($correlation, $attributes['app.correlation_id'] ?? null);
        $this->assertArrayHasKey('job.duration_ms', $attributes);
    }

    public function test_tracing_disabled_exports_no_spans_and_request_behavior_is_unchanged(): void
    {
        config(['tracing.enabled' => false]);
        $this->app->forgetInstance(TraceManager::class);

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame([], $this->spans());
    }

    public function test_sampler_ratio_zero_exports_no_spans(): void
    {
        config(['tracing.sampler_ratio' => 0.0]);
        $this->app->forgetInstance(TraceManager::class);

        $this->get('/');

        $this->assertSame([], $this->spans());
    }

    public function test_sensitive_values_are_never_exported(): void
    {
        Http::fake(['https://payments.test/*' => Http::response(['id' => 'pay_123'], 200)]);

        $response = Http::withHeaders(['Authorization' => 'Bearer secret-token-abc'])
            ->post('https://payments.test/charge', [
                'card_number' => '4111111111111111',
                'cvv' => '999',
                'password' => 'hunter2',
            ]);

        $this->assertSame(200, $response->status());

        $encoded = json_encode(array_map(
            static fn (SpanDataInterface $span) => $span->getAttributes()->toArray(),
            $this->spans(),
        ));

        $this->assertStringNotContainsString('secret-token-abc', $encoded);
        $this->assertStringNotContainsString('4111111111111111', $encoded);
        $this->assertStringNotContainsString('hunter2', $encoded);
        $this->assertStringNotContainsString('Bearer', $encoded);
    }
}

class TracingTestJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        //
    }
}
