<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_customer_can_change_password(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.password.update'), [
                'current_password' => 'Password123!',
                'password' => 'ChangedPassword123!',
                'password_confirmation' => 'ChangedPassword123!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.success.password_updated'));

        $this->assertTrue(Hash::check('ChangedPassword123!', $customer->fresh()->password));
    }

    public function test_customer_cannot_change_password_with_incorrect_current_password(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->from(route('frontend.account', ['section' => 'password']))
            ->patch(route('frontend.account.password.update'), [
                'current_password' => 'WrongPassword123!',
                'password' => 'ChangedPassword123!',
                'password_confirmation' => 'ChangedPassword123!',
            ])
            ->assertRedirect(route('frontend.account', ['section' => 'password']))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Password123!', $customer->fresh()->password));
    }

    public function test_customer_cannot_change_password_when_confirmation_does_not_match(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.password.update'), [
                'current_password' => 'Password123!',
                'password' => 'ChangedPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Password123!', $customer->fresh()->password));
    }

    public function test_customer_password_change_uses_configured_secure_password_policy(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->secure_password = true;
        $settings->save();
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'customer')
            ->patch(route('frontend.account.password.update'), [
                'current_password' => 'Password123!',
                'password' => 'passwordonly',
                'password_confirmation' => 'passwordonly',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('Password123!', $customer->fresh()->password));
    }

    public function test_password_update_requires_customer_authentication(): void
    {
        $this->patch(route('frontend.account.password.update'), [])->assertRedirect(route('frontend.login'));
    }

    private function createCustomer(): Customer
    {
        return Customer::query()->create([
            'first_name' => 'Customer',
            'last_name' => 'Password',
            'email' => 'customer-password@example.test',
            'password' => 'Password123!',
            'is_active' => true,
        ]);
    }
}
