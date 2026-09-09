<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Collection;

final class CartRecommendationService
{
    private const CACHE_TTL = 300;

    private const CATEGORY_LIMIT = 3;

    private const POOL_LIMIT = 24;

    private const RECOMMENDATION_LIMIT = 5;

    /** @return Collection<int, Product> */
    public function forCart(Cart $cart, ?int $resellerId = null): Collection
    {
        $cartItems = $cart->items
            ->filter(fn ($item): bool => $item->product !== null)
            ->unique('product_id')
            ->values();

        if ($cartItems->isEmpty()) {
            return collect();
        }

        $excludedIds = $cartItems->pluck('product_id')->map(fn ($id): int => (int) $id)->all();
        $categoryWeights = $cartItems
            ->filter(fn ($item): bool => $item->product->category_id !== null)
            ->countBy(fn ($item): int => (int) $item->product->category_id)
            ->sortDesc()
            ->take(self::CATEGORY_LIMIT);

        $categoryPools = $categoryWeights->mapWithKeys(fn (int $weight, int $categoryId): array => [
            $categoryId => $this->categoryCandidateIds($categoryId, $excludedIds),
        ]);

        $selectedIds = $this->interleave($categoryPools, $categoryWeights);

        if (count($selectedIds) < self::RECOMMENDATION_LIMIT) {
            foreach ($this->globalCandidateIds($excludedIds) as $candidateId) {
                if (! in_array($candidateId, $selectedIds, true)) {
                    $selectedIds[] = $candidateId;
                }

                if (count($selectedIds) === self::RECOMMENDATION_LIMIT) {
                    break;
                }
            }
        }

        return $this->hydrate($selectedIds, $resellerId);
    }

    /** @param array<int, int> $excludedIds
     * @return array<int, int>
     */
    private function categoryCandidateIds(int $categoryId, array $excludedIds): array
    {
        $candidateIds = CacheService::rememberManaged(
            'product-catalog',
            "cart-recommendation-category-ids:{$categoryId}:v1",
            self::CACHE_TTL,
            fn (): array => Product::query()
                ->active()
                ->where('category_id', $categoryId)
                ->where('stock', '>', 0)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::POOL_LIMIT)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
        );

        return array_values(array_diff($candidateIds, $excludedIds));
    }

    /** @param array<int, int> $excludedIds
     * @return array<int, int>
     */
    private function globalCandidateIds(array $excludedIds): array
    {
        $candidateIds = CacheService::rememberManaged(
            'product-catalog',
            'cart-recommendation-global-ids:v1',
            self::CACHE_TTL,
            fn (): array => Product::query()
                ->active()
                ->where('stock', '>', 0)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::POOL_LIMIT)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
        );

        return array_values(array_diff($candidateIds, $excludedIds));
    }

    /**
     * @param  Collection<int, array<int, int>>  $categoryPools
     * @param  Collection<int, int>  $categoryWeights
     * @return array<int, int>
     */
    private function interleave(Collection $categoryPools, Collection $categoryWeights): array
    {
        $schedule = $categoryWeights
            ->flatMap(fn (int $weight, int $categoryId): array => array_fill(0, $weight, $categoryId))
            ->values()
            ->all();
        $queues = $categoryPools->map(fn (array $ids): array => array_values($ids))->all();
        $selectedIds = [];

        while ($schedule !== [] && count($selectedIds) < self::RECOMMENDATION_LIMIT) {
            $added = false;

            foreach ($schedule as $categoryId) {
                $candidateId = array_shift($queues[$categoryId]);

                if ($candidateId !== null && ! in_array($candidateId, $selectedIds, true)) {
                    $selectedIds[] = $candidateId;
                    $added = true;
                }

                if (count($selectedIds) === self::RECOMMENDATION_LIMIT) {
                    break;
                }
            }

            if (! $added) {
                break;
            }
        }

        return $selectedIds;
    }

    /** @param array<int, int> $productIds
     * @return Collection<int, Product>
     */
    private function hydrate(array $productIds, ?int $resellerId): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $products = Product::query()
            ->select([
                'id', 'uuid', 'name', 'slug', 'category_id', 'digital', 'code',
                'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count', 'created_at',
            ])
            ->active()
            ->where('stock', '>', 0)
            ->whereKey($productIds)
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
            ->sortBy(fn (Product $product): int|false => array_search($product->id, $productIds, true))
            ->values();

        ProductStatsService::attachCatalogStats($products);

        return $products;
    }
}
