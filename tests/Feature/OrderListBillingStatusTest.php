<?php

namespace Tests\Feature;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Transaction;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderListBillingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_list_query_loads_billing_status(): void
    {
        $customer = Customer::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer-'.Str::uuid().'@example.test',
            'password' => bcrypt('password'),
            'is_active' => 'active',
        ]);

        $address = $this->createAddress($customer);

        Transaction::query()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'weight' => 0,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => 'midtrans',
            'payment_type' => 'full',
            'billing_status' => TransactionBillingStatus::paid,
            'status' => TransactionStatus::packed,
            'timelimit' => now()->addDay(),
        ]);

        $orders = (new OrderRepository)->paginateForCustomer($customer, 'all', 10);

        $this->assertSame(TransactionBillingStatus::paid, $orders->firstOrFail()->billing_status);
    }

    private function createAddress(Customer $customer): CustomerAddress
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

        return CustomerAddress::query()->create([
            'customer_id' => $customer->id,
            'province_id' => $provinceId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'name' => 'Rumah',
            'phone' => '081111111111',
            'address' => 'Jl. Rumah No. 1',
            'postal_code' => '12110',
            'is_active' => true,
            'is_featured' => true,
        ]);
    }
}
