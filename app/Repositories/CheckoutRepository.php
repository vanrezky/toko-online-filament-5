<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Models\TransactionShippingDetail;
use App\Models\TransactionVoucher;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class CheckoutRepository
{
    public function activeCart(Customer $customer): ?Cart
    {
        return Cart::query()
            ->with([
                'items.product.media',
                'items.product.warehouse',
                'items.product.flashsaleProducts' => fn (Relation $query): Relation => $query
                    ->whereHas('flashsale', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->current())
                    ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                'items.product.wholesales',
                'items.productVariant.variantAttributes.productAttribute',
                'items.productVariant.variantAttributes.productAttributeOption',
            ])
            ->when($customer->reseller_id, fn (Builder $query): Builder => $query->with([
                'items.product.resellerPrices' => fn (Relation $priceQuery): Relation => $priceQuery
                    ->where('reseller_id', $customer->reseller_id)
                    ->select(['id', 'product_id', 'reseller_id', 'price']),
            ]))
            ->active()
            ->where('customer_id', $customer->getKey())
            ->first();
    }

    /** @return Collection<int, CustomerAddress> */
    public function addresses(Customer $customer): Collection
    {
        return CustomerAddress::query()
            ->with(['province', 'district', 'subDistrict', 'village'])
            ->where('customer_id', $customer->getKey())
            ->orderByDesc('is_featured')
            ->get();
    }

    public function address(Customer $customer, int $addressId): CustomerAddress
    {
        return CustomerAddress::query()->where('customer_id', $customer->getKey())->findOrFail($addressId);
    }

    public function addressWithVillage(Customer $customer, int $addressId): CustomerAddress
    {
        return CustomerAddress::query()->with('village')->where('customer_id', $customer->getKey())->findOrFail($addressId);
    }

    /** @return Collection<int, InstallmentPlan> */
    public function activeInstallmentPlans(): Collection
    {
        return InstallmentPlan::query()->active()->get();
    }

    /** @return Collection<int, Courier> */
    public function activeCouriers(): Collection
    {
        return Courier::query()->active()->get();
    }

    public function warehouseWithVillage(int $warehouseId): ?Warehouse
    {
        return Warehouse::query()->with('village')->find($warehouseId);
    }

    public function checkoutCart(Customer $customer): Cart
    {
        return Cart::query()
            ->with(['items.product.warehouse', 'items.productVariant.variantAttributes.productAttributeOption'])
            ->active()
            ->where('customer_id', $customer->getKey())
            ->firstOrFail();
    }

    public function lockCart(Customer $customer): Cart
    {
        return Cart::query()
            ->with(['items.product.media', 'items.product.warehouse', 'items.product.wholesales', 'items.productVariant.variantAttributes.productAttributeOption'])
            ->active()
            ->where('customer_id', $customer->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findPlan(int $planId): ?InstallmentPlan
    {
        return InstallmentPlan::query()->find($planId);
    }

    public function findPlanOrFail(int $planId): InstallmentPlan
    {
        return InstallmentPlan::query()->findOrFail($planId);
    }

    /** @param array<string, int|float|string|null|\BackedEnum> $attributes */
    public function createTransaction(array $attributes): Transaction
    {
        return Transaction::query()->create($attributes);
    }

    /** @param array{warehouse_id: int|string, courier_code: string, courier_name: string, price: int, weight: int|float, estimation: ?string} $attributes */
    public function createShippingDetail(Transaction $transaction, array $attributes): TransactionShippingDetail
    {
        return $transaction->shippingDetails()->create($attributes);
    }

    /** @param array{price: float|int|string, discount: float|int|string} $attributes */
    public function updateCartItemPricing(CartItem $item, array $attributes): CartItem
    {
        $item->update($attributes);

        return $item;
    }

    public function cartHasItems(Cart $cart): bool
    {
        return $cart->items()->exists();
    }

    /** @param array<string, int|float|string|bool|null|array<string, int|float|string|bool|null>> $attributes */
    public function createTransactionProduct(Transaction $transaction, array $attributes): TransactionProduct
    {
        return $transaction->products()->create($attributes);
    }

    /** @param array{voucher_code: string, voucher_name: string, voucher_type: string, discount_type: string, discount_value: int|float|string, discount_amount: int|float|string} $attributes */
    public function createTransactionVoucher(Transaction $transaction, array $attributes): TransactionVoucher
    {
        return $transaction->vouchers()->create($attributes);
    }

    public function updateCustomerTimezone(Customer $customer, string $timezone): void
    {
        $customer->update(['timezone' => $timezone]);
    }

    public function markCartCheckedOut(Cart $cart): void
    {
        $cart->update(['status' => CartStatus::Checked_out]);
    }

    /** @param list<int> $itemIds */
    public function deleteCartItems(array $itemIds): int
    {
        return CartItem::query()->whereKey($itemIds)->delete();
    }
}
