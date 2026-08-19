<?php

namespace Tests\Feature\Platform\Integration;

use App\Models\Product;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Integration\Services\IntegrationLogService;
use App\Modules\Platform\Support\Correlation;
use App\Services\ApicoidOngkirService;
use App\Services\Gateways\DTOs\WebhookResult;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CorrelationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_log_inherits_the_active_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        $subject = Product::factory()->create();

        $log = app(IntegrationLogService::class)->start([
            'direction' => 'outbound', 'provider' => 'test', 'type' => 'api', 'method' => 'GET', 'endpoint' => '/x', 'subject' => $subject,
        ]);

        $this->assertNotNull($log);
        $this->assertSame($correlationId, $log->correlation_id);
    }

    public function test_explicit_correlation_id_overrides_the_active_context(): void
    {
        Correlation::set((string) Str::uuid());
        $explicit = 'explicit-'.substr((string) Str::uuid(), 0, 24);

        $log = app(IntegrationLogService::class)->start([
            'direction' => 'outbound', 'provider' => 'test', 'type' => 'api', 'method' => 'GET', 'endpoint' => '/x', 'correlation_id' => $explicit,
        ]);

        $this->assertNotNull($log);
        $this->assertSame($explicit, $log->correlation_id);
    }

    public function test_outbound_courier_request_carries_the_x_correlation_id_header(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);
        config()->set('regional.apicoid.base_url', 'https://courier.example.test');
        config()->set('regional.apicoid.key', 'courier-secret');
        Http::fake(['*' => Http::response(['data' => ['price' => 20000]])]);

        app(ApicoidOngkirService::class)->getShippingCost('origin', 'destination', 1000);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Correlation-ID', $correlationId));
    }

    public function test_pending_request_macro_adds_the_correlation_header(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);

        $request = new PendingRequest;
        $withCorrelation = $request->withCorrelation();

        $this->assertInstanceOf(PendingRequest::class, $withCorrelation);
        $this->assertSame($correlationId, $withCorrelation->getOptions()['headers']['X-Correlation-ID'] ?? null);
    }

    public function test_inbound_webhook_inherits_the_inbound_correlation_id(): void
    {
        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('isGatewayAvailable')->once()->with('midtrans')->andReturn(true);
        $gateway->shouldReceive('handleWebhook')->once()->andReturn(new WebhookResult(
            success: true,
            action: WebhookResult::ACTION_PROCESS,
            message: 'Webhook processed successfully',
            transactionId: 'missing-transaction',
            status: 'success',
            metadata: ['gross_amount' => 10000],
        ));
        $this->app->instance(PaymentGatewayService::class, $gateway);

        $inbound = 'wh-'.substr((string) Str::uuid(), 0, 30);

        $this->withHeaders(['X-Correlation-ID' => $inbound])
            ->postJson('/webhooks/payment/midtrans', [
                'order_id' => 'missing-transaction',
                'signature_key' => 'signature-secret',
            ])
            ->assertOk();

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::DIRECTION_INBOUND, $log->direction);
        $this->assertSame($inbound, $log->correlation_id);
    }
}
