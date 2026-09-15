<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Flashsale;
use App\Models\Product;
use App\Models\Slider;
use App\Repositories\CatalogRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class CatalogService
{
    public function __construct(
        private readonly CatalogRepository $catalogRepository,
        private readonly ProductSearchService $productSearchService,
        private readonly RelatedProductService $relatedProductService,
    ) {}

    public function homepageFlashsale(int $limit, ?int $resellerId): ?Flashsale
    {
        $flashsale = $this->catalogRepository->homepageFlashsale($limit, $resellerId);

        if ($flashsale) {
            ProductStatsService::attachCatalogStats($flashsale->products->pluck('product')->filter()->values());
        }

        return $flashsale;
    }

    public function homepageProducts(int $categoryId, int $limit, ?int $resellerId): Paginator
    {
        $products = $this->catalogRepository->homepageProducts($categoryId, $limit, $resellerId);
        ProductStatsService::attachCatalogStats($products->getCollection());

        return $products;
    }

    /** @return EloquentCollection<int, Category> */
    public function homepageCategories(): EloquentCollection
    {
        $categories = CacheService::rememberManaged(
            'frontend',
            'frontend_categories',
            3600,
            fn (): EloquentCollection => $this->catalogRepository->homepageCategories(),
        );

        return $categories instanceof EloquentCollection
            ? $categories
            : $this->catalogRepository->homepageCategories();
    }

    /** @return EloquentCollection<int, Slider> */
    public function visibleSliders(): EloquentCollection
    {
        $sliders = CacheService::rememberManaged(
            'frontend',
            Slider::CACHE_KEY,
            Slider::CACHE_TTL,
            fn (): EloquentCollection => $this->catalogRepository->visibleSliders(),
        );

        return $sliders instanceof EloquentCollection
            ? $sliders
            : $this->catalogRepository->visibleSliders();
    }

    /**
     * @param array{
     *     categories: list<string>,
     *     search: string,
     *     sort: string,
     *     price_min: ?float,
     *     price_max: ?float,
     *     rating_min: ?float,
     *     promos: list<string>,
     *     per_page: int,
     *     variants: array<string, list<string>>,
     *     query: array<string, string|list<string>>
     * } $filters
     */
    public function paginateProducts(array $filters, ?int $resellerId): LengthAwarePaginator
    {
        $query = $this->catalogRepository->catalogQuery($resellerId);

        if ($filters['categories'] !== []) {
            $query->whereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->whereIn('slug', $filters['categories']));
        }

        if ($filters['search'] !== '') {
            $this->productSearchService->apply($query, $filters['search']);
        }

        if ($filters['price_min'] !== null) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if ($filters['price_max'] !== null) {
            $query->where('price', '<=', $filters['price_max']);
        }

        $this->applyVariantFilters($query, $filters['variants']);
        $this->applyCatalogFilters($query, $filters);

        match ($filters['sort'] ?? 'newest') {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->latest(),
        };

        $products = $query->paginate($filters['per_page'])->appends($this->compactQuery($filters['query'] ?? []));
        ProductStatsService::attachCatalogStats($products->getCollection());

        return $products;
    }

    public function productDetail(Product $product): Product
    {
        $product = $this->catalogRepository->loadProductDetail($product);
        ProductStatsService::attachSales(collect([$product]));

        return $product;
    }

    /** @return Collection<int, Product> */
    public function relatedProducts(Product $product, ?int $resellerId): Collection
    {
        return $this->relatedProductService->forProduct($product, $resellerId);
    }

    /** @return EloquentCollection<int, Category> */
    public function activeCategories(): EloquentCollection
    {
        return $this->catalogRepository->activeCategories();
    }

    public function currentFlashsale(): ?Flashsale
    {
        return $this->catalogRepository->currentFlashsale();
    }

    public function flashsaleProducts(Flashsale $flashsale): LengthAwarePaginator
    {
        return $this->catalogRepository->flashsaleProducts($flashsale);
    }

    /** @param array<string, int|float|string|list<string>> $query
     * @return array<string, int|float|string|list<string>>
     */
    private function compactQuery(array $query): array
    {
        return collect($query)
            ->map(fn (int|float|string|array $value): int|float|string|array => is_array($value) && count($value) === 1 ? (string) $value[0] : $value)
            ->all();
    }

    /** @param array<string, list<string>> $variants */
    private function applyVariantFilters(Builder $query, array $variants): void
    {
        $aliases = [
            'color' => ['Warna', 'Color'],
            'size' => ['Ukuran', 'Size'],
            'gender' => ['Gender'],
        ];

        foreach ($variants as $key => $values) {
            $attributeNames = $aliases[$key] ?? [];

            if ($attributeNames === []) {
                continue;
            }

            $query->whereHas('productVariants.variantAttributes', function (Builder $attributeQuery) use ($attributeNames, $values): void {
                $attributeQuery
                    ->whereHas('productAttribute', fn (Builder $query): Builder => $query->whereIn('name', $attributeNames))
                    ->whereHas('productAttributeOption', fn (Builder $query): Builder => $query->whereIn('name', $values));
            });
        }
    }

    /**
     * @param  array{rating_min: ?float, promos: list<string>}  $filters
     */
    private function applyCatalogFilters(Builder $query, array $filters): void
    {
        if ($filters['rating_min'] !== null) {
            $query->whereRaw(
                '(SELECT COALESCE(AVG(product_reviews.rating), 0) FROM product_reviews WHERE product_reviews.product_id = products.id) >= ?',
                [$filters['rating_min']],
            );
        }

        foreach ($filters['promos'] as $promo) {
            match ($promo) {
                'discount' => $query->where(function (Builder $discountQuery): void {
                    $discountQuery
                        ->whereColumn('sale_price', '<', 'price')
                        ->orWhereHas('flashsaleProducts', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->whereHas('flashsale', fn (Builder $query): Builder => $query->current()));
                }),
                'new' => $query->where('created_at', '>=', now()->subDays(30)),
                'flash_sale' => $query->whereHas('flashsaleProducts', fn (Builder $flashsaleQuery): Builder => $flashsaleQuery->whereHas('flashsale', fn (Builder $query): Builder => $query->current())),
                default => null,
            };
        }
    }
}
