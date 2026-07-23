<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\TransactionProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductStatsService
{
    public static function attachCatalogStats(Collection $products): void
    {
        $ids = $products->pluck('id')->filter()->values();
        if ($ids->isEmpty()) return;

        $stats = self::rememberBatch('catalog', $ids, function () use ($ids): array {
            $reviews = ProductReview::query()
                ->whereIn('product_id', $ids)
                ->selectRaw('product_id, COUNT(*) as review_count, COALESCE(AVG(rating), 0) as rating_average')
                ->groupBy('product_id')->get()->keyBy('product_id');
            $sales = self::completedSales($ids);

            return $ids->mapWithKeys(fn ($id) => [(int) $id => [
                'review_count' => (int) ($reviews[$id]?->review_count ?? 0),
                'rating_average' => round((float) ($reviews[$id]?->rating_average ?? 0), 1),
                'completed_sold_count' => (int) ($sales[$id] ?? 0),
            ]])->all();
        });

        $products->each(function (Product $product) use ($stats): void {
            $stat = $stats[$product->id] ?? [];
            $product->setAttribute('reviews_count', $stat['review_count'] ?? 0);
            $product->setAttribute('reviews_avg_rating', $stat['rating_average'] ?? 0);
            $product->setAttribute('completed_sold_count', $stat['completed_sold_count'] ?? 0);
        });
    }

    public static function attachSales(Collection $products): void
    {
        $ids = $products->pluck('id')->filter()->values();
        if ($ids->isEmpty()) return;

        $sales = self::rememberBatch('sales', $ids, fn () => self::completedSales($ids)->all());
        $products->each(fn (Product $product) => $product->setAttribute('completed_sold_count', (int) ($sales[$product->id] ?? 0)));
    }

    public static function bustProduct(int $productId): void
    {
        CacheService::delete("product-stats-version:{$productId}");
        CacheService::delete("product-rating-summary:{$productId}");
    }

    public static function bustProducts(iterable $productIds): void
    {
        foreach (collect($productIds)->filter()->unique() as $productId) self::bustProduct((int) $productId);
    }

    private static function completedSales(Collection $ids): Collection
    {
        return TransactionProduct::query()
            ->whereIn('product_id', $ids)
            ->whereHas('transaction', fn ($query) => $query->where('status', 'completed'))
            ->selectRaw('product_id, SUM(quantity) as sold_count')
            ->groupBy('product_id')
            ->pluck('sold_count', 'product_id');
    }

    private static function rememberBatch(string $type, Collection $ids, callable $callback): array
    {
        $versions = $ids->map(fn ($id) => CacheService::remember("product-stats-version:{$id}", 86400, fn () => Str::random(12)));
        $key = 'product-stats:' . $type . ':' . sha1($ids->implode(',') . '|' . $versions->implode('|'));

        return CacheService::remember($key, 600, $callback);
    }
}
