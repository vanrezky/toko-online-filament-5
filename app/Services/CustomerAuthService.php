<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerAuthRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;

final class CustomerAuthService
{
    public function __construct(private readonly CustomerAuthRepository $authRepository) {}

    public function authenticate(string $email, string $password, bool $remember): bool
    {
        return Auth::guard('customer')->attempt([
            'email' => $email,
            'password' => $password,
        ], $remember);
    }

    /** @param array{first_name: string, last_name: string, email: string, password: string} $attributes */
    public function register(array $attributes): Customer
    {
        $customer = $this->authRepository->createCustomer([
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
            'is_active' => true,
        ]);
        Auth::guard('customer')->login($customer);

        return $customer;
    }

    public function socialCustomer(SocialiteUser $socialUser, string $provider): Customer
    {
        $socialAccount = $this->authRepository->findSocialAccount($provider, (string) $socialUser->getId());
        if ($socialAccount?->customer instanceof Customer) {
            return $socialAccount->customer;
        }

        $customer = $this->authRepository->findCustomerByEmail((string) $socialUser->getEmail());
        if ($customer === null) {
            [$firstName, $lastName] = $this->nameParts($socialUser);
            $customer = $this->authRepository->createCustomer([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $socialUser->getEmail(),
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }

        $this->authRepository->createSocialAccount($customer, $provider, (string) $socialUser->getId());

        return $customer;
    }

    public function loginSocial(SocialiteUser $socialUser, string $provider): Customer
    {
        $customer = $this->socialCustomer($socialUser, $provider);
        Auth::guard('customer')->login($customer, true);

        return $customer;
    }

    public function logout(): void
    {
        Auth::guard('customer')->logout();
    }

    /** @return array{0: string, 1: ?string} */
    private function nameParts(SocialiteUser $socialUser): array
    {
        $parts = preg_split('/\s+/', trim((string) ($socialUser->getName() ?: '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $parts[0] ?? Str::before((string) $socialUser->getEmail(), '@');

        return [(string) $firstName, count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null];
    }
}
