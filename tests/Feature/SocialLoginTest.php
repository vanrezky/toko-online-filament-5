<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('providers')]
    public function test_it_redirects_to_the_selected_social_provider(string $provider): void
    {
        Socialite::fake($provider);

        $this->get(route('frontend.auth.social.redirect', ['provider' => $provider]))
            ->assertRedirect("https://socialite.fake/{$provider}/authorize");
    }

    #[DataProvider('providers')]
    public function test_it_creates_and_authenticates_a_customer_from_social_login(string $provider): void
    {
        Socialite::fake($provider, SocialiteUser::fake([
            'id' => "{$provider}-123",
            'name' => 'Jane Doe',
            'email' => "jane-{$provider}@example.test",
        ]));

        $this->get(route('frontend.auth.social.callback', ['provider' => $provider]))
            ->assertRedirect(route('frontend.home'));

        $customer = Customer::query()->where('email', "jane-{$provider}@example.test")->firstOrFail();

        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertDatabaseHas('customer_social_accounts', [
            'customer_id' => $customer->id,
            'provider' => $provider,
            'provider_id' => "{$provider}-123",
        ]);
    }

    public function test_it_links_a_social_account_to_an_existing_customer_with_the_same_email(): void
    {
        $customer = Customer::query()->create([
            'first_name' => 'Existing',
            'email' => 'existing@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-existing',
            'email' => $customer->email,
        ]));

        $this->get(route('frontend.auth.social.callback', ['provider' => 'google']))
            ->assertRedirect(route('frontend.home'));

        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customer_social_accounts', [
            'customer_id' => $customer->id,
            'provider' => 'google',
            'provider_id' => 'google-existing',
        ]);
    }

    public static function providers(): array
    {
        return [
            'Google' => ['google'],
            'GitHub' => ['github'],
        ];
    }
}
