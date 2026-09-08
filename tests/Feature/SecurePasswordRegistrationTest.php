<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecurePasswordRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_shares_when_secure_password_guidance_is_enabled(): void
    {
        $this->setSecurePassword(true);

        $this->get(route('frontend.signup'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('secure_password', true)
            );
    }

    public function test_registration_page_hides_secure_password_guidance_when_disabled(): void
    {
        $this->setSecurePassword(false);

        $this->get(route('frontend.signup'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->where('secure_password', false)
            );
    }

    public function test_registration_requires_letters_numbers_and_symbols_when_secure_password_is_enabled(): void
    {
        $this->setSecurePassword(true);

        $this->post(route('frontend.signup.post'), $this->registrationData('Password1'))
            ->assertSessionHasErrors('password');
    }

    public function test_registration_accepts_a_minimum_length_password_when_secure_password_is_disabled(): void
    {
        $this->setSecurePassword(false);

        $this->post(route('frontend.signup.post'), $this->registrationData('password'))
            ->assertRedirect(route('frontend.home'));
    }

    private function setSecurePassword(bool $enabled): void
    {
        $settings = app(GeneralSettings::class);
        $settings->secure_password = $enabled;
        $settings->registration = true;
        $settings->save();
    }

    private function registrationData(string $password): array
    {
        return [
            'first_name' => 'Secure',
            'last_name' => 'Password',
            'email' => 'secure-password@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }
}
