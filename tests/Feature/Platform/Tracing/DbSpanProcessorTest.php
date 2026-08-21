<?php

namespace Tests\Feature\Platform\Tracing;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use ArrayObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DbSpanProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tracing.enabled' => true,
            'tracing.exporter' => 'memory',
            'tracing.sampler_ratio' => 1.0,
            'tracing.persist' => true,
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

    public function test_ended_span_is_persisted_with_full_span_data(): void
    {
        $correlation = (string) Str::uuid();

        $this->get('/', ['X-Correlation-ID' => $correlation])->assertOk();

        $span = TracingSpan::query()->where('name', 'GET /')->first();

        $this->assertNotNull($span);
        $this->assertSame(32, strlen((string) $span->trace_id));
        $this->assertSame(16, strlen((string) $span->span_id));
        $this->assertNull($span->parent_span_id);
        $this->assertSame('UNSET', $span->status_code);
        $this->assertNotNull($span->end_ns);
        $this->assertGreaterThanOrEqual(0, (int) $span->duration_ms);
        $this->assertSame($correlation, $span->correlation_id);
        $this->assertSame('/', $span->operation);
        $this->assertSame(2, (int) $span->kind);
    }

    public function test_persisted_attributes_are_allow_listed_only(): void
    {
        $this->get('/')->assertOk();

        $attributes = TracingSpan::query()
            ->where('name', 'GET /')
            ->firstOrFail()
            ->attributes;

        foreach ($attributes as $key => $_) {
            $this->assertContains($key, TracingAttributes::all());
        }
    }

    public function test_sensitive_values_are_never_persisted(): void
    {
        Http::fake(['https://payments.test/*' => Http::response(['id' => 'pay_123'], 200)]);

        Http::withHeaders(['Authorization' => 'Bearer secret-token-abc'])
            ->post('https://payments.test/charge', [
                'card_number' => '4111111111111111',
                'cvv' => '999',
                'password' => 'hunter2',
            ]);

        $encoded = json_encode(TracingSpan::query()->pluck('attributes')->all());

        $this->assertStringNotContainsString('secret-token-abc', $encoded);
        $this->assertStringNotContainsString('4111111111111111', $encoded);
        $this->assertStringNotContainsString('hunter2', $encoded);
        $this->assertStringNotContainsString('Bearer', $encoded);
    }

    public function test_persistence_disabled_writes_no_rows(): void
    {
        config(['tracing.persist' => false]);
        $this->app->forgetInstance(TraceManager::class);

        $this->get('/')->assertOk();

        $this->assertSame(0, TracingSpan::query()->count());
    }

    public function test_persistence_failure_does_not_break_the_request(): void
    {
        TracingSpan::creating(static function (): void {
            throw new RuntimeException('Persistence failed.');
        });

        try {
            $this->get('/')->assertOk();
        } finally {
            TracingSpan::flushEventListeners();
        }
    }
}
