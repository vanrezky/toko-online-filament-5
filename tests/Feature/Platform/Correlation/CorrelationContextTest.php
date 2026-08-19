<?php

namespace Tests\Feature\Platform\Correlation;

use App\Modules\Platform\Support\Correlation;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Support\CorrelationTestChannel;
use Tests\TestCase;

class CorrelationContextTest extends TestCase
{
    public function test_request_without_id_generates_a_uuid_and_echoes_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $requestId = $response->headers->get('X-Request-ID');
        $correlationId = $response->headers->get('X-Correlation-ID');

        $this->assertNotNull($requestId);
        $this->assertSame($requestId, $correlationId);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $requestId);
        $this->assertSame($requestId, Correlation::get());
    }

    public function test_valid_inbound_id_is_preserved_and_echoed_on_both_headers(): void
    {
        $inbound = 'req-'.substr((string) Str::uuid(), 0, 28);

        $response = $this->withHeaders([
            'X-Request-ID' => $inbound,
        ])->get('/');

        $response->assertOk();
        $this->assertSame($inbound, $response->headers->get('X-Request-ID'));
        $this->assertSame($inbound, $response->headers->get('X-Correlation-ID'));
        $this->assertSame($inbound, Correlation::get());
    }

    public function test_x_correlation_id_is_accepted_as_an_alternative_inbound_header(): void
    {
        $inbound = 'corr-'.substr((string) Str::uuid(), 0, 27);

        $response = $this->withHeaders([
            'X-Correlation-ID' => $inbound,
        ])->get('/');

        $response->assertOk();
        $this->assertSame($inbound, $response->headers->get('X-Request-ID'));
        $this->assertSame($inbound, $response->headers->get('X-Correlation-ID'));
    }

    public function test_malformed_inbound_id_is_safely_regenerated(): void
    {
        $response = $this->withHeaders([
            'X-Request-ID' => 'bad value with spaces',
        ])->get('/');

        $response->assertOk();
        $requestId = $response->headers->get('X-Request-ID');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $requestId);
        $this->assertNotSame('bad value with spaces', $requestId);
    }

    public function test_oversized_inbound_id_is_safely_regenerated(): void
    {
        $oversized = str_repeat('a', 37);

        $response = $this->withHeaders([
            'X-Request-ID' => $oversized,
        ])->get('/');

        $response->assertOk();
        $requestId = $response->headers->get('X-Request-ID');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $requestId);
        $this->assertNotSame($oversized, $requestId);
    }

    public function test_log_lines_include_the_active_correlation_id(): void
    {
        config()->set('logging.channels.correlation_test', [
            'driver' => 'custom',
            'via' => CorrelationTestChannel::class,
        ]);

        $response = $this->get('/');
        $correlationId = $response->headers->get('X-Correlation-ID');

        Log::channel('correlation_test')->info('correlation-check');

        $records = CorrelationTestChannel::$handler->getRecords();
        $this->assertNotEmpty($records);
        $this->assertSame($correlationId, $records[0]->extra['correlation_id'] ?? null);
    }

    public function test_context_is_cleared_when_the_job_scope_resets(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        $this->assertSame($correlationId, Context::get('correlation_id'));

        app()->forgetScopedInstances();
        Facade::clearResolvedInstances();

        $this->assertNull(Correlation::get());
    }
}
