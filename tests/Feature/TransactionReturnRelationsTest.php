<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionReturn;
use App\Models\TransactionReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionReturnRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_transaction_return_and_items_with_expected_relations(): void
    {
        $geo = $this->createGeo();
        $warehouse = $this->createWarehouse((int) $geo['sub_district_id']);
        $customer = $this->createCustomer(2_000_000);
        $address = $this->createAddress($customer->id, $geo);
        $product = $this->createProduct((int) $warehouse->id, 120_000);

        $transaction = Transaction::query()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'weight' => 100,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => 'bayar_penuh',
            'payment_type' => 'full',
            'billing_status' => 'pending',
            'status' => 'completed',
            'notes' => 'test return',
            'timelimit' => now()->addDay(),
        ]);

        $transactionProduct = $transaction->products()->create([
            'customer_id' => $customer->id,
            'is_digital' => false,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'price' => 120_000,
            'discount' => 0,
            'description' => null,
        ]);

        $return = TransactionReturn::query()->create([
            'transaction_id' => $transaction->id,
            'customer_id' => $customer->id,
            'status' => 'requested',
            'reason' => 'Produk rusak',
            'notes' => 'Kemasan penyok',
            'requested_at' => now(),
        ]);

        $returnItem = TransactionReturnItem::query()->create([
            'transaction_return_id' => $return->id,
            'transaction_product_id' => $transactionProduct->id,
            'qty' => 1,
            'amount' => 120_000,
            'reason' => 'Segel terbuka',
        ]);

        $transaction->refresh();
        $return->refresh();
        $returnItem->refresh();

        $this->assertCount(1, $transaction->returns);
        $this->assertTrue($transaction->returns->first()->is($return));
        $this->assertTrue($return->transaction->is($transaction));
        $this->assertTrue($return->customer->is($customer));
        $this->assertCount(1, $return->items);
        $this->assertTrue($return->items->first()->is($returnItem));
        $this->assertTrue($returnItem->transactionReturn->is($return));
        $this->assertTrue($returnItem->transactionProduct->is($transactionProduct));
        $this->assertSame('120000.00', $returnItem->amount);
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
        return \App\Models\CustomerAddress::query()->create([
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
