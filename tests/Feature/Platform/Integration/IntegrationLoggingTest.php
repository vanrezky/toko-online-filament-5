<?php

namespace Tests\Feature\Platform\Integration;

use App\Models\Product;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Integration\Services\IntegrationLogService;
use App\Modules\Platform\Integration\Support\IntegrationLogSanitizer;
use App\Services\ApicoidOngkirService;
use App\Services\Gateways\DTOs\WebhookResult;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class IntegrationLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitizer_masks_nested_sensitive_values_and_bounds_payloads(): void
    {
        config()->set('integration-logging.payload_max_bytes', 30);

        $sanitizer = app(IntegrationLogSanitizer::class);
        $sanitized = $sanitizer->sanitize([
            'Authorization' => 'Bearer top-secret',
            'nested' => ['access_token' => 'nested-secret', 'safe' => 'value'],
        ]);
        $body = $sanitizer->captureBody(['payload' => str_repeat('a', 100)]);

        $this->assertSame('********', $sanitized['Authorization']);
        $this->assertSame('********', $sanitized['nested']['access_token']);
        $this->assertSame('value', $sanitized['nested']['safe']);
        $this->assertTrue($body['truncated']);
    }

    public function test_outbound_courier_success_creates_a_sanitized_integration_log(): void
    {
        config()->set('regional.apicoid.base_url', 'https://courier.example.test');
        config()->set('regional.apicoid.key', 'courier-secret');
        Http::fake([
            '*' => Http::response(['data' => ['price' => 20000]], 200, ['Content-Type' => 'application/json']),
        ]);

        $result = app(ApicoidOngkirService::class)->getShippingCost('origin', 'destination', 1000);
        $log = IntegrationLog::query()->sole();

        $this->assertSame(['data' => ['price' => 20000]], $result);
        $this->assertSame(IntegrationLog::DIRECTION_OUTBOUND, $log->direction);
        $this->assertSame('apicoid', $log->provider);
        $this->assertSame(IntegrationLog::STATUS_SUCCESS, $log->status);
        $this->assertSame(200, $log->status_code);
        $this->assertSame('********', $log->request_headers['x-api-co-id']);
        $this->assertSame('origin', $log->request_body['origin_village_code']);
    }

    public function test_outbound_transport_exception_is_logged_and_rethrown(): void
    {
        config()->set('regional.apicoid.base_url', 'https://courier.example.test');
        config()->set('regional.apicoid.key', 'courier-secret');
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->expectException(ConnectionException::class);

        try {
            app(ApicoidOngkirService::class)->getShippingCost('origin', 'destination', 1000);
        } finally {
            $log = IntegrationLog::query()->sole();
            $this->assertSame(IntegrationLog::STATUS_FAILED, $log->status);
            $this->assertSame(ConnectionException::class, $log->error_class);
        }
    }

    public function test_outbound_http_failure_is_logged_without_changing_json_response_contract(): void
    {
        config()->set('regional.apicoid.base_url', 'https://courier.example.test');
        config()->set('regional.apicoid.key', 'courier-secret');
        Http::fake(['*' => Http::response(['message' => 'Unavailable'], 503)]);

        $result = app(ApicoidOngkirService::class)->getShippingCost('origin', 'destination', 1000);
        $log = IntegrationLog::query()->sole();

        $this->assertSame(['message' => 'Unavailable'], $result);
        $this->assertSame(IntegrationLog::STATUS_FAILED, $log->status);
        $this->assertSame(503, $log->status_code);
    }

    public function test_inbound_midtrans_webhook_is_logged_without_changing_response_contract(): void
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

        $this->postJson('/webhooks/payment/midtrans', [
            'order_id' => 'missing-transaction',
            'signature_key' => 'signature-secret',
            'nested' => ['token' => 'nested-secret'],
        ], ['Authorization' => 'Bearer webhook-secret'])
            ->assertOk()
            ->assertExactJson(['status' => 'success']);

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::DIRECTION_INBOUND, $log->direction);
        $this->assertSame('midtrans', $log->provider);
        $this->assertSame(IntegrationLog::STATUS_SUCCESS, $log->status);
        $this->assertSame('********', $log->request_headers['authorization']);
        $this->assertSame('********', $log->request_body['nested']['token']);
    }

    public function test_related_log_lifecycles_share_a_correlation_id(): void
    {
        $logger = app(IntegrationLogService::class);
        $subject = Product::factory()->create();

        $first = $logger->start([
            'direction' => 'outbound', 'provider' => 'first', 'type' => 'api', 'method' => 'GET', 'endpoint' => '/one', 'subject' => $subject,
        ]);
        $second = $logger->start([
            'direction' => 'outbound', 'provider' => 'second', 'type' => 'api', 'method' => 'GET', 'endpoint' => '/two',
        ]);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->correlation_id, $second->correlation_id);
        $this->assertSame(Product::class, $first->subject_type);
        $this->assertSame($subject->id, $first->subject_id);
    }

    public function test_pruning_removes_only_logs_older_than_configured_retention(): void
    {
        config()->set('integration-logging.retention_days', 30);
        $old = IntegrationLog::query()->create([
            'direction' => 'outbound', 'provider' => 'old', 'type' => 'api', 'status' => 'success',
            'correlation_id' => 'a1ca8c52-0d84-42bc-9db4-5e561df4ba0f', 'started_at' => now()->subDays(31),
        ]);
        $old->forceFill(['created_at' => now()->subDays(31), 'updated_at' => now()->subDays(31)])->saveQuietly();
        $recent = IntegrationLog::query()->create([
            'direction' => 'outbound', 'provider' => 'recent', 'type' => 'api', 'status' => 'success',
            'correlation_id' => 'b1ca8c52-0d84-42bc-9db4-5e561df4ba0f', 'started_at' => now(),
        ]);

        $this->artisan('integration-logs:prune')->assertSuccessful();

        $this->assertDatabaseMissing('integration_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('integration_logs', ['id' => $recent->id]);
    }
}
