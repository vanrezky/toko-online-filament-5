<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerSocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('frontend.login')->with('error', __('auth.social_login_failed'));
        }

        if (blank($socialUser->getEmail())) {
            return redirect()->route('frontend.login')->with('error', __('auth.social_email_required'));
        }

        $socialAccount = CustomerSocialAccount::query()
            ->with('customer')
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        $customer = $socialAccount?->customer ?? $this->findOrCreateCustomer($socialUser);

        if (! $socialAccount) {
            CustomerSocialAccount::create([
                'customer_id' => $customer->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
        }

        Auth::guard('customer')->login($customer, true);
        request()->session()->regenerate();

        return redirect()->intended(route('frontend.home'));
    }

    private function findOrCreateCustomer(SocialiteUser $socialUser): Customer
    {
        $customer = Customer::query()->where('email', $socialUser->getEmail())->first();

        if ($customer) {
            return $customer;
        }

        abort_if(settings('is_private_store', false), 403);

        [$firstName, $lastName] = $this->nameParts($socialUser);

        return Customer::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $socialUser->getEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(40)),
            'is_active' => true,
        ]);
    }

    private function nameParts(SocialiteUser $socialUser): array
    {
        $parts = preg_split('/\s+/', trim($socialUser->getName() ?: '')) ?: [];
        $firstName = $parts[0] ?? Str::before($socialUser->getEmail(), '@');

        return [$firstName, count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null];
    }

    private function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }
}
