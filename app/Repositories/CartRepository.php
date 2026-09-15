<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class CartRepository
{
    public function activeForCustomer(Customer $customer): ?Cart
    {
        return Cart::query()
            ->with([
                'items.product.media',
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

    public function lockActiveForCustomer(Customer $customer): Cart
    {
        $cart = Cart::query()
            ->active()
            ->where('customer_id', $customer->getKey())
            ->lockForUpdate()
            ->first();

        if ($cart === null) {
            $cart = Cart::query()->create([
                'customer_id' => $customer->getKey(),
                'status' => CartStatus::Active,
            ]);
        }

        return $cart->load(['items.product', 'items.productVariant']);
    }

    public function findProductForUpdate(string $uuid): Product
    {
        return Product::query()->withCount('productVariants')->lockForUpdate()->where('uuid', $uuid)->firstOrFail();
    }

    public function findVariantForUpdate(string $uuid): ?ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->where('uuid', $uuid)->first();
    }

    public function findItemForUpdate(Cart $cart, Product $product, ?ProductVariant $variant): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cart->getKey())
            ->where('product_id', $product->getKey())
            ->when($variant, fn (Builder $query): Builder => $query->where('product_variant_id', $variant->getKey()))
            ->when($variant === null, fn (Builder $query): Builder => $query->whereNull('product_variant_id'))
            ->lockForUpdate()
            ->first();
    }

    public function createItem(Cart $cart, Product $product, ?ProductVariant $variant, int $quantity, float|int $price, float|int $discount): CartItem
    {
        return $cart->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant?->getKey(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
        ]);
    }

    /** @param array{quantity: int, price: float|int, discount: float|int} $attributes */
    public function updateItem(CartItem $item, array $attributes): CartItem
    {
        $item->update($attributes);

        return $item;
    }

    public function ownsActiveItem(Customer $customer, CartItem $item): bool
    {
        return $item->cart()
            ->where('customer_id', $customer->getKey())
            ->where('status', CartStatus::Active)
            ->exists();
    }

    public function lockItem(CartItem $item): CartItem
    {
        return CartItem::query()
            ->with(['product' => fn (Relation $query): Relation => $query->withCount('productVariants'), 'productVariant'])
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function lockProduct(int $productId): Product
    {
        return Product::query()->withCount('productVariants')->lockForUpdate()->findOrFail($productId);
    }

    public function lockVariant(int $variantId): ?ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->find($variantId);
    }

    public function deleteItem(CartItem $item): bool
    {
        return (bool) $item->delete();
    }
}
