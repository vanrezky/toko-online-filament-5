<?php

namespace Tests\Feature;

use App\Enums\TransactionBillingStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PayrollExportService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayrollFinalSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => 'billing_due_month_offset'],
            ['payload' => json_encode(0), 'updated_at' => now()]
        );

        $this->app->forgetInstance(GeneralSettings::class);
    }

    public function test_export_draft_does_not_update_billing_or_payroll_statuses(): void
    {
        $service = app(PayrollExportService::class);

        [$fullTransaction, $installmentPayment] = $this->seedBillingItems();

        $service->exportToExcel(6, 2026);

        $fullTransaction->refresh();
        $installmentPayment->refresh();

        $this->assertSame(TransactionBillingStatus::pending, $fullTransaction->billing_status);
        $this->assertSame('scheduled', $installmentPayment->payroll_status);
        $this->assertNull($installmentPayment->payroll_batch_reference);
        $this->assertNull($installmentPayment->submitted_at);
    }

    public function test_export_final_submits_full_and_installment_items_and_returns_batch_reference(): void
    {
        $service = app(PayrollExportService::class);

        [$fullTransaction, $installmentPayment] = $this->seedBillingItems();

        $result = $service->submitAndExportFinal(6, 2026);

        $fullTransaction->refresh();
        $installmentPayment->refresh();

        $this->assertSame(TransactionBillingStatus::submitted, $fullTransaction->billing_status);
        $this->assertSame('submitted', $installmentPayment->payroll_status);
        $this->assertNotNull($installmentPayment->payroll_batch_reference);
        $this->assertNotNull($installmentPayment->submitted_at);

        $this->assertSame(2, $result['total_updated']);
        $this->assertSame(1, $result['updated_full_bills']);
        $this->assertSame(1, $result['updated_installments']);
        $this->assertNotEmpty($result['batch_reference']);
        $this->assertNotNull($result['response']);
    }

    private function seedBillingItems(): array
    {
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $customer = $this->createCustomer(2_000_000);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 100_000);

        $installmentPlan = InstallmentPlan::query()->create([
            'name' => 'Plan 6 Bulan',
            'tenor' => 6,
            'fee_percentage' => 0,
            'is_active' => true,
        ]);

        $installmentTransaction = $this->createTransaction($customer->id, $address->id, 'installment');
        $installmentTransaction->products()->create([
            'customer_id' => $customer->id,
            'is_digital' => false,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 1,
            'price' => 200_000,
            'discount' => 0,
            'line_subtotal' => 200_000,
            'description' => null,
        ]);

        $installment = Installment::query()->create([
            'transaction_id' => $installmentTransaction->id,
            'customer_id' => $customer->id,
            'installment_plan_id' => $installmentPlan->id,
            'principal_amount' => 1_200_000,
            'fee_amount' => 0,
            'total_amount' => 1_200_000,
            'monthly_amount' => 200_000,
            'tenor' => 6,
            'paid_amount' => 0,
            'paid_installments' => 0,
            'status' => 'active',
            'start_date' => '2026-05-01',
            'expected_end_date' => '2026-10-01',
        ]);

        $installmentPayment = InstallmentPayment::query()->create([
            'installment_id' => $installment->id,
            'installment_number' => 1,
            'amount' => 200_000,
            'due_date' => '2026-06-05',
            'billing_month' => '2026-06-01',
            'status' => 'unpaid',
            'payment_method' => 'payroll_deduction',
            'collection_method' => 'payroll_deduction',
            'payroll_status' => 'scheduled',
        ]);

        $fullTransaction = $this->createTransaction($customer->id, $address->id, 'full');
        $fullTransaction->update([
            'billing_due_date' => '2026-06-05',
            'billing_status' => 'pending',
        ]);
        $fullTransaction->products()->create([
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

        return [$fullTransaction, $installmentPayment];
    }

    private function createTransaction(int $customerId, int $addressId, string $paymentType): Transaction
    {
        return Transaction::query()->create([
            'customer_id' => $customerId,
            'customer_address_id' => $addressId,
            'weight' => 100,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => $paymentType === 'full' ? 'bayar_penuh' : 'cicilan',
            'payment_type' => $paymentType,
            'billing_due_date' => null,
            'billing_status' => $paymentType === 'full' ? 'pending' : 'not_applicable',
            'status' => 'completed',
            'notes' => 'test',
            'timelimit' => now()->addDay(),
        ]);
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
            'name' => 'Main Warehouse',
            'sub_district_id' => $subDistrictId,
            'address' => 'Jl. Gudang No. 1',
            'contact_name' => 'Admin Gudang',
            'contact_phone' => '081234567891',
            'description' => 'Gudang utama',
            'is_active' => true,
        ]);
    }

    private function createAddress(int $customerId, array $geo)
    {
        return CustomerAddress::query()->create([
            'customer_id' => $customerId,
            'province_id' => $geo['province_id'],
            'district_id' => $geo['district_id'],
            'sub_district_id' => $geo['sub_district_id'],
            'village_id' => $geo['village_id'],
            'name' => 'Alamat Utama',
            'phone' => '081234567890',
            'address' => 'Jl. Testing No. 1',
            'postal_code' => '12110',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }

    private function createProduct(int $warehouseId, float $price): Product
    {
        $user = User::query()->create([
            'name' => 'Admin Test',
            'email' => 'admin-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
        ]);

        return Product::query()->create([
            'name' => 'Produk Test',
            'code' => 'PROD-'.Str::upper(Str::random(8)),
            'slug' => 'produk-test-'.Str::lower(Str::random(8)),
            'description' => 'Deskripsi produk test',
            'price' => $price,
            'sale_price' => null,
            'weight' => 500,
            'stock' => 100,
            'security_stock' => 0,
            'digital' => false,
            'min_order' => 1,
            'is_active' => 1,
            'category_id' => DB::table('categories')->insertGetId([
                'name' => 'Kategori Test '.Str::random(5),
                'slug' => 'kategori-test-'.Str::random(5),
                'is_active' => true,
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'warehouse_id' => $warehouseId,
            'user_id' => $user->id,
        ]);
    }
}
