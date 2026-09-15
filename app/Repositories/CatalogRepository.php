<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Models\Flashsale;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;

final class CatalogRepository
{
    public function homepageFlashsale(int $limit, ?int $resellerId): ?Flashsale
    {
        return Flashsale::query()
            ->current()
            ->with([
                'products' => fn (Relation $query): Relation => $query
                    ->select(['id', 'flashsale_id', 'product_id', 'discount_percentage', 'stock'])
                    ->limit($limit),
                'products.product' => fn (Relation $query): Relation => $query->select([
                    'id',
                    'uuid',
                    'name',
                    'slug',
                    'digital',
                    'code',
                    'stock',
                    'sale_price',
                    'price',
                    'min_order',
                    'fake_sold_count',
                ]),
                'products.product.media',
                'products.product.flashsaleProducts' => fn (Relation $query): Relation => $query
                    ->whereHas('flashsale', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->current())
                    ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                'products.product.wholesales' => fn (Relation $query): Relation => $query
                    ->where('min_qty', '<=', 1)
                    ->select(['id', 'product_id', 'min_qty', 'price']),
            ])
            ->when($resellerId, fn (Builder $query): Builder => $query->with([
                'products.product.resellerPrices' => fn (Relation $priceQuery): Relation => $priceQuery
                    ->where('reseller_id', $resellerId)
                    ->select(['id', 'product_id', 'reseller_id', 'price']),
            ]))
            ->first();
    }

    public function homepageProducts(int $categoryId, int $limit, ?int $resellerId): Paginator
    {
        return $this->baseProductQuery($resellerId)
            ->when($categoryId > 0, fn (Builder $query): Builder => $query->where('category_id', $categoryId))
            ->latest()
            ->simplePaginate($limit)
            ->withQueryString();
    }

    /** @return Collection<int, Category> */
    public function homepageCategories(): Collection
    {
        return Category::homepage()->with('media')->get();
    }

    /** @return Collection<int, Slider> */
    public function visibleSliders(): Collection
    {
        return Slider::query()->visible()->ordered()->with('media')->get();
    }

    public function catalogQuery(?int $resellerId): Builder
    {
        return $this->baseProductQuery($resellerId);
    }

    /** @return Collection<int, Category> */
    public function activeCategories(): Collection
    {
        return Category::active()->orderBy('name')->get();
    }

    public function loadProductDetail(Product $product): Product
    {
        return $product->loadMissing([
            'category',
            'productVariants.variantAttributes' => fn (Relation $query): Relation => $query->with([
                'productAttribute',
                'productAttributeOption',
            ]),
            'warehouse',
            'media',
            'flashsaleProducts' => fn (Relation $query): Relation => $query
                ->whereHas('flashsale', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->current())
                ->select(['id', 'product_id', 'discount_percentage', 'stock']),
            'faqs',
            'meta',
            'wholesales',
            'resellerPrices',
        ]);
    }

    public function currentFlashsale(): ?Flashsale
    {
        return Flashsale::query()->current()->withCount('products')->first();
    }

    public function flashsaleProducts(Flashsale $flashsale): LengthAwarePaginator
    {
        return $flashsale->products()
            ->whereHas('product', fn (Builder $query): Builder => $query->active())
            ->with([
                'product.media',
                'product.category',
                'product.resellerPrices',
                'product.wholesales',
            ])
            ->latest('id')
            ->paginate(16)
            ->withQueryString();
    }

    private function baseProductQuery(?int $resellerId): Builder
    {
        return Product::query()
            ->select([
                'id',
                'uuid',
                'name',
                'slug',
                'digital',
                'code',
                'stock',
                'sale_price',
                'price',
                'min_order',
                'fake_sold_count',
                'created_at',
            ])
            ->active()
            ->with([
                'media',
                'flashsaleProducts' => fn (Relation $query): Relation => $query
                    ->whereHas('flashsale', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->current())
                    ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                'wholesales' => fn (Relation $query): Relation => $query
                    ->where('min_qty', '<=', 1)
                    ->select(['id', 'product_id', 'min_qty', 'price']),
            ])
            ->when($resellerId, fn (Builder $query): Builder => $query->with([
                'resellerPrices' => fn (Relation $priceQuery): Relation => $priceQuery
                    ->where('reseller_id', $resellerId)
                    ->select(['id', 'product_id', 'reseller_id', 'price']),
            ]));
    }
}
