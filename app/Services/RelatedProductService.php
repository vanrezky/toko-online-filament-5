<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

final class RelatedProductService
{
    private const LIMIT = 6;

    private const CACHE_TTL = 300;

    public function forProduct(Product $product, ?int $resellerId = null): Collection
    {
        $relatedProductIds = array_map('intval', CacheService::rememberManaged(
            'product-catalog',
            'related-product-ids:'.($product->category_id ?? 'none').':'.$product->id,
            self::CACHE_TTL,
            fn () => Product::query()
                ->active()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::LIMIT)
                ->pluck('id')
                ->all(),
        ));

        $relatedProducts = Product::query()
            ->select([
                'id', 'uuid', 'name', 'slug', 'digital', 'code',
                'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count', 'created_at',
            ])
            ->active()
            ->whereKey($relatedProductIds)
            ->with([
                'media',
                'flashsaleProducts' => fn ($query) => $query
                    ->whereHas('flashsale', fn ($query) => $query->current())
                    ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                'wholesales' => fn ($query) => $query
                    ->where('min_qty', '<=', 1)
                    ->select(['id', 'product_id', 'min_qty', 'price']),
            ])
            ->when($resellerId, fn ($query) => $query->with([
                'resellerPrices' => fn ($query) => $query
                    ->where('reseller_id', $resellerId)
                    ->select(['id', 'product_id', 'reseller_id', 'price']),
            ]))
            ->get()
            ->sortBy(fn (Product $relatedProduct) => array_search($relatedProduct->id, $relatedProductIds, true))
            ->values();

        ProductStatsService::attachCatalogStats($relatedProducts);

        return $relatedProducts;
    }
}
