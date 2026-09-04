<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAccessModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_store_allows_guest_auth_pages_and_registration(): void
    {
        $this->setStoreMode(false);

        $this->get(route('frontend.login'))->assertOk();
        $this->get(route('frontend.signup'))->assertOk();
        $this->get(route('frontend.forgot-password'))->assertOk();
        $this->get(route('frontend.reset-password', ['token' => 'sample-token']))->assertOk();

        $response = $this->post(route('frontend.signup.post'), [
            'first_name' => 'Public',
            'last_name' => 'Customer',
            'email' => 'public-customer@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('frontend.home'));
        $this->assertDatabaseHas('customers', [
            'email' => 'public-customer@example.test',
        ]);
        $this->assertAuthenticated('customer');
    }

    public function test_private_store_blocks_registration_but_keeps_other_auth_pages_accessible(): void
    {
        $this->setStoreMode(true);

        $this->get(route('frontend.login'))->assertOk();
        $this->get(route('frontend.forgot-password'))->assertOk();
        $this->get(route('frontend.reset-password', ['token' => 'sample-token']))->assertOk();

        $this->get(route('frontend.signup'))
            ->assertRedirect(route('frontend.registration-closed'));

        $this->get(route('frontend.registration-closed'))->assertOk();

        $this->post(route('frontend.signup.post'), [
            'first_name' => 'Blocked',
            'last_name' => 'Customer',
            'email' => 'blocked-customer@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('customers', [
            'email' => 'blocked-customer@example.test',
        ]);
    }

    public function test_private_store_still_allows_customer_login(): void
    {
        $this->setStoreMode(true);

        $customer = Customer::query()->create([
            'first_name' => 'Existing',
            'last_name' => 'Customer',
            'email' => 'existing-customer@example.test',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post(route('frontend.login.post'), [
            'email' => $customer->email,
            'password' => 'Password123!',
            'remember' => false,
        ]);

        $response->assertRedirect(route('frontend.home'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    private function setStoreMode(bool $isPrivateStore): void
    {
        $settings = app(GeneralSettings::class);
        $settings->is_private_store = $isPrivateStore;
        $settings->save();
    }
}
