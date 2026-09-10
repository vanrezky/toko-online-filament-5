<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountProfileDeferredPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_profile_defers_secondary_datasets(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->get(route('frontend.account'))
            ->assertInertia(function (Assert $page): void {
                $page
                    ->component('Account/Profile')
                    ->has('user')
                    ->has('addresses')
                    ->missing('provinces')
                    ->missing('totalOrders')
                    ->missing('recentOrders')
                    ->missing('balanceHistory')
                    ->loadDeferredProps(function (Assert $page): void {
                        $page
                            ->has('provinces')
                            ->where('totalOrders', 0)
                            ->has('recentOrders')
                            ->has('balanceHistory');
                    });
            });
    }

    public function test_account_profile_keeps_balance_history_eagerly_empty_when_disabled(): void
    {
        $this->setBalanceEnabled(false);
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->get(route('frontend.account'))
            ->assertInertia(function (Assert $page): void {
                $page
                    ->component('Account/Profile')
                    ->where('balanceEnabled', false)
                    ->where('balanceHistory', [])
                    ->missing('provinces')
                    ->missing('totalOrders')
                    ->missing('recentOrders');
            });
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Account',
            'last_name' => 'Customer',
            'email' => 'account-'.uniqid().'@example.test',
            'password' => 'Password123!',
            'is_active' => true,
        ]);
    }

    private function setBalanceEnabled(bool $enabled): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => 'balance_enabled'],
            ['payload' => json_encode($enabled), 'updated_at' => now()],
        );

        $this->app->forgetInstance(GeneralSettings::class);
    }
}
