<?php

namespace Tests\Feature\Platform\Integration;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Support\Correlation;
use App\Services\Gateways\MidtransGateway;
use App\Settings\PaymentGatewaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class MidtransGatewayIntegrationLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_create_payment_success_records_sanitized_outbound_log_with_subject(): void
    {
        $transaction = $this->createTransaction();
        $this->mockSnapToken('snap-token-123');
        $settings = $this->mockSettings();

        $gateway = new MidtransGateway($settings);
        $response = $gateway->createPayment($transaction);

        $this->assertTrue($response->success);
        $this->assertSame('snap-token-123', $response->metadata['snap_token']);

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::DIRECTION_OUTBOUND, $log->direction);
        $this->assertSame('midtrans', $log->provider);
        $this->assertSame(IntegrationLog::TYPE_API, $log->type);
        $this->assertSame('POST', $log->method);
        $this->assertSame('Snap::getSnapToken', $log->endpoint);
        $this->assertSame(IntegrationLog::STATUS_SUCCESS, $log->status);
        $this->assertSame(['snap_token' => 'snap-token-123'], $log->response_body);
        $this->assertSame($transaction->uuid, $log->request_body['transaction_details']['order_id']);
        $this->assertSame(Transaction::class, $log->subject_type);
        $this->assertSame($transaction->id, $log->subject_id);
    }

    public function test_create_payment_exception_returns_failed_response_and_logs_failed(): void
    {
        $transaction = $this->createTransaction();
        $this->mockSnapToken()->shouldReceive('getSnapToken')->andThrow(new \Exception('Midtrans API error', 500));
        $settings = $this->mockSettings();

        $response = (new MidtransGateway($settings))->createPayment($transaction);

        $this->assertFalse($response->success);
        $this->assertSame('Midtrans API error', $response->errorMessage);

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::STATUS_FAILED, $log->status);
        $this->assertSame(\Exception::class, $log->error_class);
        $this->assertSame('Midtrans API error', $log->error_message);
    }

    public function test_get_payment_status_success_records_sdk_result_and_links_subject(): void
    {
        $transaction = $this->createTransaction(withProduct: false);
        $result = (object) [
            'order_id' => $transaction->uuid,
            'transaction_status' => 'settlement',
            'gross_amount' => 100000,
            'currency' => 'IDR',
        ];
        $this->mockTransactionStatus()->shouldReceive('status')->once()->with($transaction->uuid)->andReturn($result);
        $settings = $this->mockSettings();

        $status = (new MidtransGateway($settings))->getPaymentStatus($transaction->uuid);

        $this->assertSame('success', $status->status);

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::DIRECTION_OUTBOUND, $log->direction);
        $this->assertSame('midtrans', $log->provider);
        $this->assertSame('GET', $log->method);
        $this->assertSame('Transaction::status', $log->endpoint);
        $this->assertSame(IntegrationLog::STATUS_SUCCESS, $log->status);
        $this->assertSame('settlement', $log->response_body['transaction_status']);
        $this->assertSame(Transaction::class, $log->subject_type);
        $this->assertSame($transaction->id, $log->subject_id);
    }

    public function test_get_payment_status_exception_returns_failure_and_logs_failed(): void
    {
        $this->mockTransactionStatus()->shouldReceive('status')->andThrow(new \Exception('Order not found', 404));
        $settings = $this->mockSettings();

        $status = (new MidtransGateway($settings))->getPaymentStatus('missing-uuid');

        $this->assertSame('not_found', $status->status);
        $this->assertSame('Order not found', $status->errorMessage);

        $log = IntegrationLog::query()->sole();
        $this->assertSame(IntegrationLog::STATUS_FAILED, $log->status);
        $this->assertSame(404, $log->status_code);
        $this->assertSame(\Exception::class, $log->error_class);
        $this->assertNull($log->subject_type);
    }

    public function test_payment_logs_inherit_the_active_correlation_id(): void
    {
        Correlation::set('abc-123-def');
        $transaction = $this->createTransaction();
        $this->mockSnapToken('snap-token-456');
        $settings = $this->mockSettings();

        (new MidtransGateway($settings))->createPayment($transaction);

        $this->assertSame('abc-123-def', IntegrationLog::query()->sole()->correlation_id);
    }

    private function mockSnapToken(?string $token = null): Mockery\LegacyMockInterface
    {
        $mock = Mockery::mock('overload:Midtrans\Snap');

        if ($token !== null) {
            $mock->shouldReceive('getSnapToken')->andReturn($token);
        }

        return $mock;
    }

    private function mockTransactionStatus(): Mockery\LegacyMockInterface
    {
        return Mockery::mock('overload:Midtrans\Transaction');
    }

    private function mockSettings(): PaymentGatewaySettings
    {
        $settings = Mockery::mock(PaymentGatewaySettings::class);
        $settings->shouldReceive('getActiveGatewayCredentials')->andReturn([
            'server_key' => 'SB-Mid-server-test-key',
            'client_key' => 'SB-Mid-client-test-key',
            'merchant_id' => 'G-test',
            'mode' => 'sandbox',
            'supported_currencies' => ['IDR'],
            'channels' => [],
        ]);
        $settings->shouldReceive('getSupportedCurrencies')->andReturn(['IDR']);
        $settings->shouldReceive('isConfigured')->andReturn(true);

        return $settings;
    }

    private function createTransaction(bool $withProduct = true): Transaction
    {
        $customer = Customer::query()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'customer-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
            'credit_limit' => 1_000_000,
            'is_active' => 'active',
        ]);
        $address = $this->createAddress($customer);

        $warehouse = null;
        $product = null;
        if ($withProduct) {
            $warehouse = Warehouse::query()->create([
                'name' => 'Gudang Utama',
                'sub_district_id' => $address->sub_district_id,
                'province_id' => null,
                'district_id' => null,
                'village_id' => null,
                'address' => 'Jl. Gudang No. 1',
                'contact_name' => 'Admin',
                'contact_phone' => '081111111111',
                'postal_code' => '12110',
                'courier' => 'JNE,TIKI,POS',
                'description' => 'Warehouse utama untuk pengujian',
                'is_active' => true,
            ]);
            $product = Product::factory()->create([
                'price' => 100_000,
                'sale_price' => null,
                'warehouse_id' => $warehouse->id,
            ]);
        }

        $transaction = Transaction::query()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'weight' => 500,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => 'midtrans',
            'payment_type' => 'full',
            'billing_status' => TransactionBillingStatus::pending,
            'status' => TransactionStatus::packed,
            'timelimit' => now()->addDay(),
        ]);

        if ($withProduct) {
            $transaction->products()->create([
                'customer_id' => $customer->id,
                'is_digital' => false,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 1,
                'price' => 100_000,
                'discount' => 0,
                'line_subtotal' => 100_000,
                'description' => null,
            ]);
        }

        return $transaction;
    }

    private function createAddress(Customer $customer): CustomerAddress
    {
        $countryId = DB::table('countries')->insertGetId(['iso' => 'ID', 'iso3' => 'IDN', 'name' => 'Indonesia', 'created_at' => now(), 'updated_at' => now()]);
        $provinceId = DB::table('provinces')->insertGetId(['country_id' => $countryId, 'name' => 'DKI Jakarta', 'rajaongkir' => '31', 'created_at' => now(), 'updated_at' => now()]);
        $districtId = DB::table('districts')->insertGetId(['province_id' => $provinceId, 'type' => 'Kota', 'name' => 'Jakarta Selatan', 'rajaongkir' => '3174', 'postal_code' => '12110', 'created_at' => now(), 'updated_at' => now()]);
        $subDistrictId = DB::table('sub_districts')->insertGetId(['district_id' => $districtId, 'name' => 'Kebayoran Baru', 'rajaongkir' => '3174010', 'postal_code' => '12110', 'created_at' => now(), 'updated_at' => now()]);
        $villageId = DB::table('villages')->insertGetId(['sub_district_id' => $subDistrictId, 'name' => 'Senayan', 'postal_code' => '12110', 'rajaongkir' => '3174010001', 'apicoid_code' => 'V001', 'created_at' => now(), 'updated_at' => now()]);

        return CustomerAddress::query()->create([
            'customer_id' => $customer->id,
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'village_id' => $villageId,
            'name' => 'Rumah',
            'phone' => '081111111111',
            'address' => 'Jl. Rumah No. 2',
            'postal_code' => '12110',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }
}
