<?php

namespace Tests\Feature;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermsAgreementRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_terms_acceptance_when_enabled(): void
    {
        $this->setTermAgreement(true);

        $this->post(route('frontend.signup.post'), $this->registrationData())
            ->assertSessionHasErrors('terms_accepted');
    }

    public function test_registration_does_not_require_terms_acceptance_when_disabled(): void
    {
        $this->setTermAgreement(false);

        $this->post(route('frontend.signup.post'), $this->registrationData())
            ->assertRedirect(route('frontend.home'));
    }

    private function setTermAgreement(bool $enabled): void
    {
        $settings = app(GeneralSettings::class);
        $settings->term_agreement = $enabled;
        $settings->registration = true;
        $settings->save();
    }

    private function registrationData(): array
    {
        return [
            'first_name' => 'Terms',
            'last_name' => 'Tester',
            'email' => 'terms-tester@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
    }
}
