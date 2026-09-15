<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class AccountRepository
{
    public function loadAddresses(Customer $customer): Customer
    {
        return $customer->load([
            'address.province',
            'address.district',
            'address.subDistrict',
            'address.village',
        ]);
    }

    /** @param array{first_name: string, last_name: string, email: string, phone: ?string} $attributes */
    public function updateCustomer(Customer $customer, array $attributes): Customer
    {
        $customer->update($attributes);

        return $customer->refresh();
    }

    /** @param array{name: string, phone: string, province_id: int, district_id: int, sub_district_id: int, village_id: int, address: string, postal_code: string, is_featured?: bool, source_type?: string} $attributes */
    public function createAddress(Customer $customer, array $attributes): CustomerAddress
    {
        return $customer->address()->create($attributes);
    }

    public function clearFeaturedAddresses(int $customerId, ?int $exceptId = null): int
    {
        return CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->update(['is_featured' => false]);
    }

    /** @param array{name: string, phone: string, province_id: int, district_id: int, sub_district_id: int, village_id: int, address: string, postal_code: string, is_featured?: bool} $attributes */
    public function updateAddress(CustomerAddress $address, array $attributes): CustomerAddress
    {
        $address->update($attributes);

        return $address->refresh();
    }

    public function deleteAddress(CustomerAddress $address): bool
    {
        return (bool) $address->delete();
    }

    public function countOrders(Customer $customer): int
    {
        return $this->ordersQuery($customer)->count('id');
    }

    /** @return Collection<int, Transaction> */
    public function recentOrders(Customer $customer): Collection
    {
        return $this->ordersQuery($customer)
            ->limit(5)
            ->get([
                'id',
                'uuid',
                'code',
                'customer_id',
                'status',
                'shipping_cost',
                'cod_fee',
                'created_at',
                'timelimit',
            ]);
    }

    /** @return Collection<int, array<string, int|float|string|null>> */
    public function balanceHistory(Customer $customer): Collection
    {
        return $customer->balances()
            ->latest()
            ->limit(5)
            ->get(['id', 'amount', 'post_balance', 'trx_type', 'type', 'notes', 'created_at']);
    }

    private function ordersQuery(Customer $customer): Builder
    {
        return Transaction::query()
            ->with([
                'products' => function (Relation $query): void {
                    $query->select('id', 'transaction_id', 'product_id', 'quantity', 'price', 'discount', 'description');
                },
                'products.product' => function (Relation $query): void {
                    $query->select('id', 'uuid', 'name', 'slug');
                },
                'products.product.media',
            ])
            ->where('customer_id', $customer->getKey())
            ->orderByDesc('created_at');
    }
}
