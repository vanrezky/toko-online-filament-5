<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CourierCode;
use App\Enums\InstallmentStatus;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final class OrderRepository
{
    public function paginateForCustomer(Customer $customer, string $status, int $perPage): LengthAwarePaginator
    {
        return Transaction::query()
            ->with([
                'products' => function (Relation $query): void {
                    $query->select(
                        'id', 'uuid', 'transaction_id', 'product_id', 'warehouse_id',
                        'product_name', 'product_code', 'variant_name', 'variant_sku',
                        'line_subtotal', 'quantity', 'price', 'discount', 'description', 'product_snapshot',
                    );
                },
            ])
            ->where('customer_id', $customer->getKey())
            ->when($status !== 'all', fn (Builder $query): Builder => $query->where('status', $status))
            ->latest()
            ->paginate($perPage, ['id', 'uuid', 'code', 'customer_id', 'status', 'shipping_cost', 'cod_fee', 'created_at', 'timelimit'])
            ->withQueryString();
    }

    public function loadForShow(Transaction $transaction): Transaction
    {
        $transaction->load([
            'shippingDetails.warehouse',
            'shippingDetails.warehouse.village',
            'shippingDetails.warehouse.district',
            'shippingDetails.warehouse.province',
            'vouchers',
            'products.review',
            'products' => function (Relation $query): void {
                $query->select(
                    'id', 'uuid', 'transaction_id', 'product_id', 'warehouse_id',
                    'product_name', 'product_code', 'variant_name', 'variant_sku',
                    'line_subtotal', 'quantity', 'price', 'discount', 'description', 'product_snapshot',
                );
            },
        ]);

        $hasDelivery = $transaction->shippingDetails
            ->contains(fn ($detail): bool => strtolower((string) $detail->courier_code) !== CourierCode::PICKUP->value);

        if ($hasDelivery) {
            $transaction->load([
                'address' => function (Relation $query): void {
                    $query->select('id', 'name', 'phone', 'address', 'province_id', 'district_id', 'sub_district_id', 'village_id', 'postal_code');
                },
                'address.province:id,name',
                'address.district:id,name',
                'address.subDistrict:id,name',
                'address.village:id,name',
            ]);
        }

        return $transaction;
    }

    public function cancelInstallment(Transaction $transaction): void
    {
        if ($transaction->payment_type !== 'installment' || $transaction->installment === null) {
            return;
        }

        $transaction->installment->update(['status' => InstallmentStatus::Cancelled->value]);
        $transaction->installment->payments()
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->update(['status' => 'cancelled']);
    }
}
