<?php

namespace Tests\Feature;

use App\Enums\CartStatus;
use App\Enums\TransactionStatus;
use App\Jobs\ExpireTransaction;
use App\Models\Balance;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Flashsale;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\ProductFlashsale;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Services\Gateways\DTOs\PaymentResponse;
use App\Services\Gateways\DTOs\WebhookResult;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use App\Settings\GeneralSettings;
use App\Settings\PaymentGatewaySettings;
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

        $this->setPaymentGatewaySetting('active_gateway', null);
        $this->forbidPaymentGateway();

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

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('payment.provider', 'full');

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('full', $transaction->payment_type);
        $this->assertSame('pending', $transaction->billing_status->value);
        $this->assertNotNull($transaction->billing_due_date);
        $this->assertSame(CartStatus::Checked_out->value, $cart->fresh()->status);

        $customer->refresh();
        $afterRemaining = $customer->remaining_credit_limit;

        $this->assertLessThan($beforeRemaining, $afterRemaining + 0.0001);
        $this->assertEqualsWithDelta(
            $customer->effective_credit_limit - $transaction->total_amount,
            $afterRemaining,
            0.001
        );
    }

    public function test_checkout_persists_whole_idr_amounts_for_decimal_pricing(): void
    {
        Queue::fake();

        $this->setPaymentGatewaySetting('active_gateway', null);
        $this->forbidPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000.51);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100_000.51,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'address_id' => $address->id,
            'shipping_methods' => [
                (string) $warehouse->id => [
                    'courier_code' => 'KURIR_TOKO',
                    'courier_name' => 'Kurir Toko',
                    'price' => 20_000.51,
                    'weight' => 500,
                    'estimation' => '1-2 hari',
                ],
            ],
            'payment_type' => 'full',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $transactionProduct = $transaction->products()->firstOrFail();

        $this->assertSame(100_001.0, (float) $transactionProduct->price);
        $this->assertSame(0.0, (float) $transactionProduct->discount);
        $this->assertSame(100_001.0, (float) $transactionProduct->line_subtotal);
        $this->assertSame(20_001.0, (float) $transaction->shipping_cost);
        $this->assertSame(120_002.0, (float) $transaction->total_amount);
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
            'line_subtotal' => 240_000,
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

    public function test_checkout_reserves_flashsale_stock_uses_flashsale_price_and_releases_it_when_cancelled(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $product->update(['sale_price' => 80_000]);
        $product->wholesales()->create(['min_qty' => 2, 'price' => 70_000]);

        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale checkout',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);
        $flashsaleProduct = ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 30,
            'stock' => 2,
        ]);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            // Deliberately stale: checkout must never trust this value.
            'price' => 100_000,
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
                        'weight' => 1_000,
                        'estimation' => '1-2 hari',
                    ],
                ],
                'payment_type' => 'full',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $transactionProduct = $transaction->products()->firstOrFail();
        $flashsaleProduct->refresh();

        $this->assertEqualsWithDelta(100_000, (float) $transactionProduct->price, 0.001);
        $this->assertEqualsWithDelta(30_000, (float) $transactionProduct->discount, 0.001);
        $this->assertEqualsWithDelta(140_000, (float) $transactionProduct->line_subtotal, 0.001);
        $this->assertSame(0, $flashsaleProduct->stock);
        $this->assertDatabaseHas('flashsale_reservations', [
            'transaction_id' => $transaction->id,
            'product_flashsale_id' => $flashsaleProduct->id,
            'quantity' => 2,
        ]);

        app(TransactionCancellationService::class)->cancel($transaction);

        $this->assertSame('cancelled', $transaction->fresh()->status->value);
        $this->assertSame(2, $flashsaleProduct->fresh()->stock);
        $this->assertDatabaseHas('flashsale_reservations', [
            'transaction_id' => $transaction->id,
            'product_flashsale_id' => $flashsaleProduct->id,
        ]);
        $this->assertNotNull($transaction->flashsaleReservations()->firstOrFail()->fresh()->released_at);
    }

    public function test_checkout_rejects_flashsale_when_aggregate_cart_quantity_exceeds_quota(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale kuota terbatas',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);
        $flashsaleProduct = ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 25,
            'stock' => 1,
        ]);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'cart_item_ids' => null,
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 1_000),
            'payment_type' => 'full',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(1, $flashsaleProduct->fresh()->stock);
        $this->assertSame(CartStatus::Active->value, $cart->fresh()->status);
    }

    public function test_checkout_rejects_invalid_payload_before_creating_an_order(): void
    {
        $customer = $this->createCustomer(500_000);

        $response = $this->actingAs($customer, 'customer')
            ->postJson(route('frontend.checkout.store'), []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['shipping_methods', 'payment_type']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_keeps_flashsale_reservation_when_credit_checkout_completes_without_gateway(): void
    {
        Queue::fake();
        $this->setPaymentGatewaySetting('active_gateway', null);
        $this->forbidPaymentGateway();

        $customer = $this->createCustomer(500_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $flashsale = Flashsale::query()->create([
            'name' => 'Flash sale checkout kredit internal',
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'is_active' => true,
        ]);
        $flashsaleProduct = ProductFlashsale::query()->create([
            'flashsale_id' => $flashsale->id,
            'product_id' => $product->id,
            'discount_percentage' => 25,
            'stock' => 1,
        ]);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 500),
            'payment_type' => 'full',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('payment.provider', 'full');

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('flashsale_reservations', [
            'transaction_id' => $transaction->id,
            'product_flashsale_id' => $flashsaleProduct->id,
            'quantity' => 1,
        ]);
        $this->assertSame(0, $flashsaleProduct->fresh()->stock);
        $this->assertSame(CartStatus::Checked_out->value, $cart->fresh()->status);
    }

    public function test_balance_checkout_debits_wallet_and_cancellation_refunds_it_once(): void
    {
        Queue::fake();
        $this->setGeneralSetting('balance_enabled', true);
        $this->setPaymentGatewaySetting('active_gateway', null);
        $this->forbidPaymentGateway();

        $customer = $this->createCustomer(0);
        $customer->update(['balance' => 250_000]);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 500),
            'payment_type' => 'balance',
        ]);

        $response->assertOk()->assertJsonPath('payment.provider', 'balance');
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame('balance', $transaction->payment_type);
        $this->assertSame('paid', $transaction->billing_status->value);
        $this->assertSame(130_000.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('balances', [
            'transaction_id' => $transaction->id,
            'type' => Balance::TYPE_PURCHASE,
            'post_balance' => 130000,
        ]);

        app(TransactionCancellationService::class)->cancel($transaction);
        app(TransactionCancellationService::class)->cancel($transaction->fresh());

        $this->assertSame(250_000.0, (float) $customer->fresh()->balance);
        $this->assertSame(1, Balance::query()->where('transaction_id', $transaction->id)->where('type', Balance::TYPE_REFUND)->count());
    }

    public function test_installment_checkout_completes_without_an_active_payment_gateway(): void
    {
        Queue::fake();
        $this->setPaymentGatewaySetting('active_gateway', null);
        $this->forbidPaymentGateway();

        $customer = $this->createCustomer(2_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 1_000_000);
        $plan = InstallmentPlan::query()->create([
            'name' => 'Plan 6 Bulan',
            'tenor' => 6,
            'fee_percentage' => 10,
            'is_active' => true,
        ]);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1_000_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 1_000),
            'payment_type' => 'installment',
            'installment_plan_id' => $plan->id,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('payment.provider', 'installment');

        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $installment = Installment::query()->where('transaction_id', $transaction->id)->firstOrFail();

        $this->assertSame('installment', $transaction->payment_type);
        $this->assertSame('not_applicable', $transaction->billing_status->value);
        $this->assertSame($plan->id, $transaction->installment_plan_id);
        $this->assertSame($plan->id, $installment->installment_plan_id);
        $this->assertSame(6, $installment->tenor);
        $this->assertSame(CartStatus::Checked_out->value, $cart->fresh()->status);
    }

    public function test_checkout_only_creates_transaction_products_for_selected_cart_items(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();
        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $selectedProduct = $this->createProduct((int) $warehouse->id, 100_000);
        $remainingProduct = $this->createProduct((int) $warehouse->id, 300_000);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        $selectedItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $selectedProduct->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);
        $remainingItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $remainingProduct->id,
            'quantity' => 1,
            'price' => 300_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'cart_item_ids' => [$selectedItem->uuid],
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 500),
            'payment_type' => 'full',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame([$selectedProduct->id], $transaction->products()->pluck('product_id')->all());
        $this->assertDatabaseMissing('cart_items', ['id' => $selectedItem->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $remainingItem->id]);
        $this->assertSame(CartStatus::Active->value, $cart->fresh()->status);
    }

    public function test_checkout_rejects_cart_items_outside_the_customers_active_cart(): void
    {
        $customer = $this->createCustomer(1_000_000);
        $otherCustomer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $customerCart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $customerCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);
        $otherCart = Cart::create(['customer_id' => $otherCustomer->id, 'status' => CartStatus::Active->value]);
        $otherItem = CartItem::create([
            'cart_id' => $otherCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'cart_item_ids' => [$otherItem->uuid],
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 500),
            'payment_type' => 'full',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('cart_item_ids');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_without_a_selection_still_purchases_every_active_cart_item(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();
        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $firstProduct = $this->createProduct((int) $warehouse->id, 100_000);
        $secondProduct = $this->createProduct((int) $warehouse->id, 200_000);
        $cart = Cart::create(['customer_id' => $customer->id, 'status' => CartStatus::Active->value]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $firstProduct->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $secondProduct->id,
            'quantity' => 1,
            'price' => 200_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('frontend.checkout.store'), [
            'address_id' => $address->id,
            'shipping_methods' => $this->shippingMethods($warehouse, 1_000),
            'payment_type' => 'full',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame(CartStatus::Checked_out->value, $cart->fresh()->status);
    }

    public function test_checkout_decrements_variant_and_product_stock_and_restores_once(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 4,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 6,
        ]);

        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
            ],
        );

        $response->assertOk()->assertJson(['success' => true]);
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame(8, (int) $product->fresh()->stock);
        $this->assertSame(2, (int) $variant->fresh()->stock);
        $this->assertSame($variant->id, $transaction->products()->firstOrFail()->product_variant_id);

        app(TransactionCancellationService::class)->cancel($transaction);
        app(TransactionCancellationService::class)->cancel($transaction->fresh());

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(4, (int) $variant->fresh()->stock);
    }

    public function test_checkout_rejects_stale_variant_stock_without_creating_an_order(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 1,
        ]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
            ],
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(1, (int) $variant->fresh()->stock);
        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertSame(CartStatus::Active->value, $cart->fresh()->status);
    }

    public function test_checkout_decrements_non_variant_product_stock(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $product->update(['stock' => 5]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
            ],
        );

        $response->assertOk()->assertJson(['success' => true]);
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame(3, (int) $product->fresh()->stock);
        $this->assertNull($transaction->products()->firstOrFail()->product_variant_id);
    }

    public function test_completed_order_does_not_restore_normal_stock(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $product->update(['stock' => 5]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
            ],
        );

        $response->assertOk();
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $transaction->update(['status' => TransactionStatus::completed->value]);

        app(TransactionCancellationService::class)->cancel($transaction);

        $this->assertSame(TransactionStatus::completed, $transaction->fresh()->status);
        $this->assertSame(3, (int) $product->fresh()->stock);
    }

    public function test_expired_order_restores_normal_stock_once(): void
    {
        Queue::fake();
        $this->mockPaymentGateway();

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $product->update(['stock' => 5]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
            ],
        );

        $response->assertOk();
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $transaction->update(['timelimit' => now('UTC')->subMinute()]);
        $gateway = Mockery::mock(PaymentGatewayService::class);

        (new ExpireTransaction($transaction->uuid))->handle(
            $gateway,
            app(TransactionCancellationService::class),
        );
        (new ExpireTransaction($transaction->uuid))->handle(
            $gateway,
            app(TransactionCancellationService::class),
        );

        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(TransactionStatus::cancelled, $transaction->fresh()->status);
    }

    public function test_cancelled_payment_webhook_restores_normal_stock_once(): void
    {
        Queue::fake();
        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('isGatewayAvailable')->with('midtrans')->andReturnTrue();
        $gateway->shouldReceive('getActiveGatewayAlias')->andReturn('midtrans');
        $gateway->shouldReceive('createPayment')->andReturn(new PaymentResponse(true, 'trx-123', 'https://pay.test', null));
        $this->app->instance(PaymentGatewayService::class, $gateway);

        $customer = $this->createCustomer(1_000_000);
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);
        $product->update(['stock' => 5]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.checkout.store'),
            [
                'address_id' => $address->id,
                'shipping_methods' => $this->shippingMethods($warehouse, 500),
                'payment_type' => 'full',
                'payment_method' => 'midtrans',
            ],
        );

        $response->assertOk();
        $transaction = Transaction::query()->where('customer_id', $customer->id)->latest('id')->firstOrFail();
        $gateway->shouldReceive('handleWebhook')->twice()->andReturn(new WebhookResult(
            success: true,
            action: WebhookResult::ACTION_PROCESS,
            transactionId: $transaction->uuid,
            status: 'expired',
            metadata: ['gross_amount' => (int) round($transaction->total_amount)],
        ));

        $this->postJson(route('frontend.webhooks.payment', 'midtrans'), ['order_id' => $transaction->uuid])
            ->assertOk()
            ->assertJson(['status' => 'success']);
        $this->postJson(route('frontend.webhooks.payment', 'midtrans'), ['order_id' => $transaction->uuid])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSame(5, (int) $product->fresh()->stock);
        $this->assertSame(TransactionStatus::cancelled, $transaction->fresh()->status);
    }

    public function test_product_stock_tracks_variant_create_update_and_delete(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $firstVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 2,
        ]);
        $secondVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 3,
        ]);

        $this->assertSame(5, (int) $product->fresh()->stock);

        $firstVariant->update(['stock' => 7]);
        $this->assertSame(10, (int) $product->fresh()->stock);

        $secondVariant->delete();
        $this->assertSame(7, (int) $product->fresh()->stock);

        $firstVariant->delete();
        $this->assertSame(0, (int) $product->fresh()->stock);
    }

    public function test_cart_rejects_same_variant_quantity_over_stock_and_preserves_existing_line(): void
    {
        $customer = $this->createCustomer(1_000_000);
        $product = Product::factory()->create(['stock' => 3]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 3,
        ]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        $item = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.cart.store'),
            [
                'product_id' => $product->uuid,
                'product_variant_id' => $variant->uuid,
                'quantity' => 2,
            ],
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame(2, (int) $item->fresh()->quantity);
    }

    public function test_cart_update_rejects_quantity_over_current_stock(): void
    {
        $customer = $this->createCustomer(1_000_000);
        $product = Product::factory()->create(['stock' => 2]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 2,
        ]);
        $cart = Cart::query()->create([
            'customer_id' => $customer->id,
            'status' => CartStatus::Active->value,
        ]);
        $item = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 100_000,
            'discount' => 0,
        ]);

        $response = $this->actingAs($customer, 'customer')->patchJson(
            route('frontend.cart.update', $item->uuid),
            ['quantity' => 3],
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame(1, (int) $item->fresh()->quantity);
        $this->assertSame(100_000.0, (float) $item->fresh()->price);
    }

    public function test_cart_validates_each_variant_against_its_own_stock(): void
    {
        $customer = $this->createCustomer(1_000_000);
        $product = Product::factory()->create(['stock' => 0]);
        $firstVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 1,
        ]);
        $secondVariant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 2,
        ]);

        $firstResponse = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.cart.store'),
            [
                'product_id' => $product->uuid,
                'product_variant_id' => $firstVariant->uuid,
                'quantity' => 1,
            ],
        );
        $secondResponse = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.cart.store'),
            [
                'product_id' => $product->uuid,
                'product_variant_id' => $secondVariant->uuid,
                'quantity' => 2,
            ],
        );

        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $this->assertSame(1, (int) CartItem::query()->where('product_variant_id', $firstVariant->id)->value('quantity'));
        $this->assertSame(2, (int) CartItem::query()->where('product_variant_id', $secondVariant->id)->value('quantity'));
    }

    public function test_cart_rejects_a_variant_that_belongs_to_another_product(): void
    {
        $customer = $this->createCustomer(1_000_000);
        $product = Product::factory()->create(['stock' => 2]);
        $otherProduct = Product::factory()->create(['stock' => 2]);
        $variant = ProductVariant::query()->create([
            'product_id' => $otherProduct->id,
            'sku' => 'VAR-'.Str::upper(Str::random(8)),
            'price' => 100_000,
            'stock' => 2,
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(
            route('frontend.cart.store'),
            [
                'product_id' => $product->uuid,
                'product_variant_id' => $variant->uuid,
                'quantity' => 1,
            ],
        );

        $response->assertUnprocessable()->assertJsonValidationErrors('product_variant_id');
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function mockPaymentGateway(?PaymentResponse $response = null): void
    {
        $mock = Mockery::mock(PaymentGatewayService::class);
        $mock->shouldReceive('createPayment')->andReturn($response ?? new PaymentResponse(true, 'trx-123', 'https://pay.test', null));
        $mock->shouldReceive('getActiveGatewayAlias')->andReturn('xendit');

        $this->app->instance(PaymentGatewayService::class, $mock);
    }

    private function forbidPaymentGateway(): void
    {
        $mock = Mockery::mock(PaymentGatewayService::class);
        $mock->shouldNotReceive('createPayment');

        $this->app->instance(PaymentGatewayService::class, $mock);
    }

    private function shippingMethods(Warehouse $warehouse, int $weight): array
    {
        return [
            (string) $warehouse->id => [
                'courier_code' => 'KURIR_TOKO',
                'courier_name' => 'Kurir Toko',
                'price' => 20_000,
                'weight' => $weight,
                'estimation' => '1-2 hari',
            ],
        ];
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
        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => $name],
            [
                'payload' => json_encode($value),
                'updated_at' => now(),
            ]
        );

        $this->app->forgetInstance(GeneralSettings::class);
    }

    private function setPaymentGatewaySetting(string $name, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'payment', 'name' => $name],
            [
                'payload' => json_encode($value),
                'updated_at' => now(),
            ]
        );

        $this->app->forgetInstance(PaymentGatewaySettings::class);
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
