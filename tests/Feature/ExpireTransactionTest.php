<?php

namespace Tests\Feature;

use App\Enums\TransactionBillingStatus;
use App\Enums\TransactionStatus;
use App\Jobs\ExpireTransaction;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Transaction;
use App\Services\Gateways\DTOs\PaymentStatus;
use App\Services\PaymentGatewayService;
use App\Services\TransactionCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ExpireTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_an_expired_midtrans_transaction_that_is_not_found_by_midtrans(): void
    {
        Queue::fake();
        $transaction = $this->createExpiredMidtransTransaction();
        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('getPaymentStatus')
            ->once()
            ->with($transaction->uuid)
            ->andReturn(new PaymentStatus('not_found', $transaction->uuid, null, null, 'Transaction does not exist.'));
        $cancellation = Mockery::mock(TransactionCancellationService::class);
        $cancellation->shouldReceive('cancel')
            ->once()
            ->withArgs(fn (Transaction $candidate) => $candidate->is($transaction));

        (new ExpireTransaction($transaction->uuid))->handle($gateway, $cancellation);
    }

    public function test_it_keeps_an_expired_midtrans_transaction_pending_when_status_is_unknown(): void
    {
        Queue::fake();
        $transaction = $this->createExpiredMidtransTransaction();
        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('getPaymentStatus')
            ->once()
            ->with($transaction->uuid)
            ->andReturn(new PaymentStatus('unknown', $transaction->uuid, null, null, 'Gateway timeout'));
        $cancellation = Mockery::mock(TransactionCancellationService::class);
        $cancellation->shouldNotReceive('cancel');

        (new ExpireTransaction($transaction->uuid))->handle($gateway, $cancellation);
    }

    private function createExpiredMidtransTransaction(): Transaction
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

        return Transaction::query()->create([
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'weight' => 0,
            'shipping_cost' => 0,
            'cod' => false,
            'cod_fee' => 0,
            'payment_method' => 'midtrans',
            'payment_type' => 'full',
            'billing_status' => TransactionBillingStatus::pending,
            'status' => TransactionStatus::packed,
            'timelimit' => now('UTC')->subMinute(),
        ]);
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
