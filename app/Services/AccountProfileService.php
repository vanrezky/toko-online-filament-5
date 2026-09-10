<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class AccountProfileService
{
    public function __construct(
        private readonly RegionalService $regionalService,
    ) {
    }

    public function loadAddresses(Customer $customer): Customer
    {
        return $customer->load([
            'address.province',
            'address.district',
            'address.subDistrict',
            'address.village',
        ]);
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function getProvinces(): Collection
    {
        return $this->regionalService->getProvinces()->map(
            fn ($province): array => ['id' => $province->id, 'name' => $province->name],
        );
    }

    public function getTotalOrders(Customer $customer): int
    {
        return $this->ordersQuery($customer)->count('id');
    }

    public function getRecentOrders(Customer $customer): EloquentCollection
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

    public function getBalanceHistory(Customer $customer): EloquentCollection
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
                'products' => function ($query): void {
                    $query->select('id', 'transaction_id', 'product_id', 'quantity', 'price', 'discount', 'description');
                },
                'products.product' => function ($query): void {
                    $query->select('id', 'uuid', 'name', 'slug');
                },
                'products.product.media',
            ])
            ->where('customer_id', $customer->getKey())
            ->orderBy('created_at', 'desc');
    }
}
