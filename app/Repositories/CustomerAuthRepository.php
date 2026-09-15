<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerSocialAccount;
use App\Models\User;

final class CustomerAuthRepository
{
    public function findCustomerByEmail(string $email): ?Customer
    {
        return Customer::query()->where('email', $email)->first();
    }

    /** @param array{first_name: string, last_name: string|null, email: string, password: string, is_active: bool, email_verified_at?: \DateTimeInterface} $attributes */
    public function createCustomer(array $attributes): Customer
    {
        return Customer::query()->create($attributes);
    }

    public function findSocialAccount(string $provider, string $providerId): ?CustomerSocialAccount
    {
        return CustomerSocialAccount::query()
            ->with('customer')
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function createSocialAccount(Customer $customer, string $provider, string $providerId): CustomerSocialAccount
    {
        return CustomerSocialAccount::query()->create([
            'customer_id' => $customer->getKey(),
            'provider' => $provider,
            'provider_id' => $providerId,
        ]);
    }

    public function findPasswordRecipient(string $guard, string $email): Customer|User|null
    {
        return match ($guard) {
            'customer' => Customer::query()->where('email', $email)->first(),
            default => User::query()->where('email', $email)->first(),
        };
    }
}
