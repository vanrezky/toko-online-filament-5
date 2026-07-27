<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\Customer;
use App\Models\User;
use App\Services\BalanceService;
use App\Settings\GeneralSettings;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mutations_create_an_immutable_balance_ledger(): void
    {
        $this->enableBalance();
        $customer = $this->customer();
        $admin = User::factory()->create();
        $service = app(BalanceService::class);

        $service->topUp($customer, 100_000, 'Top up awal', $admin);
        $service->reduce($customer, 25_000, 'Koreksi', $admin);

        $this->assertSame('75000.00', $customer->fresh()->balance);
        $this->assertDatabaseHas('balances', [
            'customer_id' => $customer->id,
            'performed_by_id' => $admin->id,
            'type' => Balance::TYPE_TOP_UP,
            'balance_before' => 0,
            'post_balance' => 100000,
        ]);
        $this->assertDatabaseHas('balances', [
            'customer_id' => $customer->id,
            'type' => Balance::TYPE_ADJUSTMENT_DEBIT,
            'balance_before' => 100000,
            'post_balance' => 75000,
        ]);
    }

    public function test_balance_cannot_be_reduced_below_zero_or_mutated_when_disabled(): void
    {
        $this->disableBalance();
        $customer = $this->customer();
        $service = app(BalanceService::class);

        try {
            $service->topUp($customer, 10_000, 'Top up');
            $this->fail('Disabled balance feature accepted a top up.');
        } catch (DomainException) {
        }

        $this->enableBalance();
        $service->topUp($customer, 10_000, 'Top up');

        $this->expectException(DomainException::class);
        $service->reduce($customer, 10_001, 'Tidak boleh minus');
    }

    private function enableBalance(): void
    {
        $this->setBalanceEnabled(true);
    }

    private function disableBalance(): void
    {
        $this->setBalanceEnabled(false);
    }

    private function setBalanceEnabled(bool $enabled): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => 'balance_enabled'],
            ['payload' => json_encode($enabled), 'updated_at' => now()],
        );
        $this->app->forgetInstance(GeneralSettings::class);
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Balance',
            'last_name' => 'Customer',
            'email' => 'balance-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'is_active' => 'active',
        ]);
    }
}
