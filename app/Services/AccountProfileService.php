<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Repositories\AccountRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

final class AccountProfileService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly RegionalService $regionalService,
    ) {}

    public function loadAddresses(Customer $customer): Customer
    {
        return $this->accountRepository->loadAddresses($customer);
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function getProvinces(): Collection
    {
        return $this->regionalService->getProvinces()->map(
            fn ($province): array => ['id' => (int) $province->id, 'name' => (string) $province->name],
        );
    }

    public function getTotalOrders(Customer $customer): int
    {
        return $this->accountRepository->countOrders($customer);
    }

    public function getRecentOrders(Customer $customer): EloquentCollection
    {
        return $this->accountRepository->recentOrders($customer);
    }

    public function getBalanceHistory(Customer $customer): EloquentCollection
    {
        return $this->accountRepository->balanceHistory($customer);
    }

    /** @param array{first_name: string, last_name: string, email: string, phone: ?string} $attributes */
    public function updateProfile(Customer $customer, array $attributes, ?UploadedFile $image = null): Customer
    {
        $customer = $this->accountRepository->updateCustomer($customer, $attributes);

        if ($image !== null) {
            $customer->clearMediaCollection('profile_photos');
            $media = $customer->addMedia($image)->toMediaCollection('profile_photos', config('filesystems.upload_disk'));
            $customer->update(['image' => $media->getUrl('thumb')]);
        }

        return $customer;
    }

    public function updatePassword(Customer $customer, string $password): void
    {
        $customer->update(['password' => $password]);
    }

    /** @param array{name: string, phone: string, province_id: int, district_id: int, sub_district_id: int, village_id: int, address: string, postal_code: string, is_featured?: bool} $attributes */
    public function createAddress(Customer $customer, array $attributes): CustomerAddress
    {
        $isFeatured = (bool) ($attributes['is_featured'] ?? false);

        if ($isFeatured) {
            $this->accountRepository->clearFeaturedAddresses((int) $customer->getKey());
        }

        return $this->accountRepository->createAddress($customer, [
            ...$attributes,
            'is_featured' => $isFeatured,
            'source_type' => 'customer',
        ]);
    }

    /** @param array{name: string, phone: string, province_id: int, district_id: int, sub_district_id: int, village_id: int, address: string, postal_code: string, is_featured?: bool} $attributes */
    public function updateAddress(CustomerAddress $address, array $attributes): CustomerAddress
    {
        $isFeatured = (bool) ($attributes['is_featured'] ?? false);

        if ($isFeatured) {
            $this->accountRepository->clearFeaturedAddresses((int) $address->customer_id, (int) $address->getKey());
            $attributes['is_featured'] = true;
        } else {
            unset($attributes['is_featured']);
        }

        return $this->accountRepository->updateAddress($address, $attributes);
    }

    public function deleteAddress(CustomerAddress $address): bool
    {
        return $this->accountRepository->deleteAddress($address);
    }
}
