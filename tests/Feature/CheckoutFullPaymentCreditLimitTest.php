<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Services\Gateways\DTOs\PaymentResponse;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CheckoutFullPaymentCreditLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_full_checkout_reduces_remaining_credit_limit(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 150_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 150_000,
            'discount' => 0,
        ]);

        $beforeRemaining = $customer->remaining_credit_limit;

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 20_000,
                        'weight' => 1_000,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'test',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('full', $transaction->payment_type);
        $this->assertSame('pending', $transaction->billing_status);

        $customer->refresh();
        $afterRemaining = $customer->remaining_credit_limit;

        $this->assertLessThan($beforeRemaining, $afterRemaining + 0.0001);
        $this->assertEqualsWithDelta(
            $customer->effective_credit_limit - $transaction->total_amount,
            $afterRemaining,
            0.001
        );
    }

    public function test_full_checkout_is_rejected_when_credit_limit_is_insufficient(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();

        $customer = $this->createCustomer(100_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 150_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 20_000,
                        'weight' => 500,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'test',
            ]);

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'error' => 'Limit kredit tidak mencukupi',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_full_checkout_is_allowed_when_credit_limit_enforcement_is_disabled(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();
        $this->setGeneralSetting('enforce_credit_limit', false);

        $customer = $this->createCustomer(100_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 150_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 20_000,
                        'weight' => 500,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'credit enforcement disabled',
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_full_checkout_uses_cumulative_outstanding_for_limit_validation(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);

        $existingTransaction = Transaction::query()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'weight' => 500,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => 'bayar_penuh',
            'payment_type' => 'full',
            'billing_due_date' => now()->addMonth()->startOfMonth(),
            'billing_status' => 'pending',
            'status' => 'packed',
            'notes' => 'existing outstanding',
            'timelimit' => now()->addDay(),
        ]);

        $existingProduct = $this->createProduct((int) $warehouse->id, 120_000);
        $existingTransaction->products()->create([
            'customer_id' => $customer->id,
            'is_digital' => false,
            'product_id' => $existingProduct->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'price' => 120_000,
            'discount' => 0,
            'description' => null,
        ]);

        $product = $this->createProduct((int) $warehouse->id, 200_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 200_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 10_000,
                        'weight' => 1_000,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'mixed outstanding test',
            ]);

        $response->assertStatus(400)->assertJson([
            'success' => false,
            'error' => 'Limit kredit tidak mencukupi',
        ]);

        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_full_checkout_before_or_on_cutoff_sets_due_date_from_current_cycle(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();
        $this->setBillingSettings(25, 5, 1);
        Carbon::setTestNow('2026-05-25 10:00:00');

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 150_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 20_000,
                        'weight' => 500,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'cycle current month test',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('2026-06-05', optional($transaction->billing_due_date)->toDateString());
    }

    public function test_full_checkout_after_cutoff_sets_due_date_from_next_cycle(): void
    {
        Queue::fake();

        $this->mockPaymentGateway();
        $this->setBillingSettings(25, 5, 1);
        Carbon::setTestNow('2026-05-26 10:00:00');

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 150_000);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), [
                'address_id' => $address->id,
                'shipping_methods' => [
                    (string) $warehouse->id => [
                        'courier_code' => 'KURIR_TOKO',
                        'courier_name' => 'Kurir Toko',
                        'price' => 20_000,
                        'weight' => 500,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
                'notes' => 'cycle next month test',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('2026-07-05', optional($transaction->billing_due_date)->toDateString());
    }

    private function mockPaymentGateway(): void
    {
        $mock = Mockery::mock(PaymentGatewayService::class);
        $mock->shouldReceive('createPayment')->andReturn(new PaymentResponse(true, 'trx-123', 'https://pay.test', null));
        $mock->shouldReceive('getActiveGatewayAlias')->andReturn('xendit');

        $this->app->instance(PaymentGatewayService::class, $mock);
    }

    private function createCustomer(float $creditLimit): Customer
    {
        return Customer::query()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'customer-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
            'credit_limit' => $creditLimit,
            'is_active' => 'active',
        ]);
    }

    private function setBillingSettings(int $cutoffDay, int $dueDay, int $monthOffset): void
    {
        $this->setGeneralSetting('billing_cutoff_day', $cutoffDay);
        $this->setGeneralSetting('billing_due_day', $dueDay);
        $this->setGeneralSetting('billing_due_month_offset', $monthOffset);
    }

    private function setGeneralSetting(string $name, mixed $value): void
    {
        DB::table('settings')
            ->where('group', 'general')
            ->where('name', $name)
            ->update([
                'payload' => json_encode($value),
            ]);
    }

    private function createGeo(): array
    {
        $countryId = DB::table('countries')->insertGetId([
            'iso' => 'ID',
            'iso3' => 'IDN',
            'name' => 'Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $provinceId = DB::table('provinces')->insertGetId([
            'country_id' => $countryId,
            'name' => 'DKI Jakarta',
            'rajaongkir' => '31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $districtId = DB::table('districts')->insertGetId([
            'province_id' => $provinceId,
            'type' => 'Kota',
            'name' => 'Jakarta Selatan',
            'rajaongkir' => '3174',
            'postal_code' => '12110',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subDistrictId = DB::table('sub_districts')->insertGetId([
            'district_id' => $districtId,
            'name' => 'Kebayoran Baru',
            'rajaongkir' => '3174010',
            'postal_code' => '12110',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $villageId = DB::table('villages')->insertGetId([
            'sub_district_id' => $subDistrictId,
            'name' => 'Senayan',
            'postal_code' => '12110',
            'rajaongkir' => '3174010001',
            'apicoid_code' => 'V001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'village_id' => $villageId,
        ];
    }

    private function createWarehouse(int $subDistrictId): Warehouse
    {
        return Warehouse::query()->create([
            'name' => 'Warehouse A',
            'sub_district_id' => $subDistrictId,
            'address' => 'Jl. Gudang No.1',
            'contact_name' => 'Admin',
            'contact_phone' => '081234567890',
            'description' => 'Main warehouse',
            'is_active' => true,
        ]);
    }

    private function createAddress(int $customerId, array $geo): CustomerAddress
    {
        return CustomerAddress::query()->create([
            'customer_id' => $customerId,
            'province_id' => $geo['province_id'],
            'district_id' => $geo['district_id'],
            'sub_district_id' => $geo['sub_district_id'],
            'village_id' => $geo['village_id'],
            'name' => 'Rumah',
            'phone' => '081111111111',
            'address' => 'Jl. Rumah No.2',
            'postal_code' => '12110',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }

    private function createProduct(int $warehouseId, float $price): Product
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Admin '.Str::uuid(),
            'email' => 'admin-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Kategori '.Str::uuid(),
            'slug' => 'kategori-'.Str::random(8),
            'is_active' => true,
            'is_featured' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Produk Test',
            'slug' => 'produk-test-'.Str::random(8),
            'category_id' => $categoryId,
            'warehouse_id' => $warehouseId,
            'digital' => false,
            'description' => 'Produk untuk test checkout',
            'code' => 'PTEST-'.Str::upper(Str::random(6)),
            'stock' => 100,
            'security_stock' => 1,
            'weight' => 500,
            'price' => $price,
            'sale_price' => null,
            'afiliate_price' => null,
            'min_order' => 1,
            'variant' => null,
            'sub_variant' => null,
            'is_active' => true,
            'user_id' => $userId,
        ]);
    }
}
