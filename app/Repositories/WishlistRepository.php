<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

final class WishlistRepository
{
    /** @return Collection<int, Product> */
    public function productsForCustomer(Customer $customer, ?int $resellerId): Collection
    {
        return Wishlist::query()
            ->where('customer_id', $customer->getKey())
            ->with([
                'product' => function (Relation $query): void {
                    $query->select([
                        'id', 'uuid', 'name', 'slug', 'digital', 'code', 'stock',
                        'sale_price', 'price', 'min_order', 'fake_sold_count',
                    ]);
                },
                'product.media',
                'product.flashsaleProducts' => function (Relation $query): void {
                    $query->whereHas('flashsale', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->current())
                        ->select(['id', 'product_id', 'discount_percentage', 'stock']);
                },
                'product.wholesales' => fn (Relation $query): Relation => $query
                    ->where('min_qty', '<=', 1)
                    ->select(['id', 'product_id', 'min_qty', 'price']),
            ])
            ->when($resellerId, fn (Builder $query): Builder => $query->with([
                'product.resellerPrices' => fn (Relation $priceQuery): Relation => $priceQuery
                    ->where('reseller_id', $resellerId)
                    ->select(['id', 'product_id', 'reseller_id', 'price']),
            ]))
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->values();
    }

    public function findByProductUuid(string $uuid): Product
    {
        return Product::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function findItem(Customer $customer, Product $product): ?Wishlist
    {
        return Wishlist::query()
            ->where('customer_id', $customer->getKey())
            ->where('product_id', $product->getKey())
            ->first();
    }

    public function add(Customer $customer, Product $product): Wishlist
    {
        return Wishlist::query()->create([
            'customer_id' => $customer->getKey(),
            'product_id' => $product->getKey(),
        ]);
    }

    public function remove(Wishlist $wishlist): bool
    {
        return (bool) $wishlist->delete();
    }
}
