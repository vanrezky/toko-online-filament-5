<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\ProductSearchService;
use App\Services\ProductStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    private const PAGE_SIZES = [12, 24, 36];

    private const RELATED_PRODUCTS_LIMIT = 6;

    private const RELATED_PRODUCTS_CACHE_TTL = 300;

    private const VARIANT_ATTRIBUTE_ALIASES = [
        'color' => ['Warna', 'Color'],
        'size' => ['Ukuran', 'Size'],
        'gender' => ['Gender'],
    ];

    public function __invoke(Request $request, ProductSearchService $productSearchService)
    {
        $filters = $this->normalizeFilters($request);
        $resellerId = auth('customer')->user()?->reseller_id;

        $query = Product::query()
            ->select([
                'id', 'uuid', 'name', 'slug', 'digital', 'code',
                'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count', 'created_at',
            ])
            ->active()
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
            ]));

        if ($filters['categories'] !== []) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->whereIn('slug', $filters['categories']);
            });
        }

        if ($filters['search'] !== '') {
            $productSearchService->apply($query, $filters['search']);
        }

        if ($filters['price_min'] !== null) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if ($filters['price_max'] !== null) {
            $query->where('price', '<=', $filters['price_max']);
        }

        $this->applyVariantFilters($query, $filters['variants']);
        $this->applyCatalogFilters($query, $filters);

        switch ($filters['sort']) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $products = $query->paginate($filters['per_page'])->appends($this->compactQuery($filters['query']));
        ProductStatsService::attachCatalogStats($products->getCollection());
        $categories = Category::active()->orderBy('name')->get();

        return Inertia::render('Products/Index', [
            'products' => ProductSimpleResource::collection($products),
            'categories' => CategoryResource::collection($categories),
            'filters' => $filters['query'],
        ]);
    }

    private function normalizeFilters(Request $request): array
    {
        $priceMin = $this->normalizeNumber($request->input('price_min'));
        $priceMax = $this->normalizeNumber($request->input('price_max'));

        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        $sort = (string) $request->input('sort', 'newest');
        $sort = in_array($sort, ['newest', 'price_low', 'price_high', 'name_asc', 'name_desc'], true) ? $sort : 'newest';

        $perPage = (int) $request->input('per_page', 12);
        $perPage = in_array($perPage, self::PAGE_SIZES, true) ? $perPage : 12;

        $ratingMin = $this->normalizeNumber($request->input('rating_min'));
        $ratingMin = $ratingMin === null ? null : min(5, max(1, $ratingMin));

        $promos = $this->normalizeList($request->input('promo', $request->input('promotion')));
        $promos = array_values(array_intersect($promos, ['discount', 'new', 'flash_sale']));

        $variants = [];
        foreach ($request->query() as $key => $value) {
            if (! Str::startsWith($key, 'variant_')) {
                continue;
            }

            $filterKey = Str::lower(Str::after($key, 'variant_'));
            if (! array_key_exists($filterKey, self::VARIANT_ATTRIBUTE_ALIASES)) {
                continue;
            }

            $values = $this->normalizeList($value);

            if ($filterKey !== '' && $values !== []) {
                $variants[$filterKey] = $values;
            }
        }

        $query = array_filter([
            'search' => trim((string) $request->input('search')),
            'category' => $this->normalizeList($request->input('category')),
            'sort' => $sort !== 'newest' ? $sort : null,
            'price_min' => $this->formatFilterNumber($priceMin),
            'price_max' => $this->formatFilterNumber($priceMax),
            'rating_min' => $ratingMin,
            'promo' => $promos !== [] ? $promos : null,
            'per_page' => $perPage !== 12 ? $perPage : null,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);

        foreach ($variants as $key => $values) {
            $query['variant_'.$key] = $values;
        }

        $categories = $this->normalizeList($request->input('category'));
        if (count($categories) === 1) {
            $query['category'] = $categories[0];
        }

        return [
            'search' => trim((string) $request->input('search')),
            'categories' => $categories,
            'sort' => $sort,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'rating_min' => $ratingMin,
            'promos' => $promos,
            'per_page' => $perPage,
            'variants' => $variants,
            'query' => $query,
        ];
    }

    private function applyVariantFilters($query, array $variants): void
    {
        foreach ($variants as $key => $values) {
            $attributeNames = self::VARIANT_ATTRIBUTE_ALIASES[$key] ?? [];

            if ($attributeNames === []) {
                continue;
            }

            $query->whereHas('productVariants.variantAttributes', function ($attributeQuery) use ($attributeNames, $values): void {
                $attributeQuery
                    ->whereHas('productAttribute', fn ($query) => $query->whereIn('name', $attributeNames))
                    ->whereHas('productAttributeOption', fn ($query) => $query->whereIn('name', $values));
            });
        }
    }

    private function applyCatalogFilters($query, array $filters): void
    {
        if ($filters['rating_min'] !== null) {
            $query->whereRaw(
                '(SELECT COALESCE(AVG(product_reviews.rating), 0) FROM product_reviews WHERE product_reviews.product_id = products.id) >= ?',
                [$filters['rating_min']]
            );
        }

        foreach ($filters['promos'] as $promo) {
            match ($promo) {
                'discount' => $query->where(function ($query): void {
                    $query->whereColumn('sale_price', '<', 'price')
                        ->orWhereHas('flashsaleProducts', fn ($query) => $query->whereHas('flashsale', fn ($query) => $query->current()));
                }),
                'new' => $query->where('created_at', '>=', now()->subDays(30)),
                'flash_sale' => $query->whereHas('flashsaleProducts', fn ($query) => $query->whereHas('flashsale', fn ($query) => $query->current())),
                default => null,
            };
        }
    }

    private function normalizeList(mixed $value): array
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->flatMap(fn ($item) => preg_split('/\s*,\s*/', trim((string) $item), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (float) $value);
    }

    private function formatFilterNumber(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    private function compactQuery(array $query): array
    {
        return collect($query)
            ->map(fn ($value) => is_array($value) && count($value) === 1 ? $value[0] : $value)
            ->all();
    }

    public function show(Request $request, Product $product)
    {
        $product->loadMissing([
            'category',
            'productVariants.variantAttributes' => fn ($query) => $query->with([
                'productAttribute',
                'productAttributeOption',
            ]),
            'warehouse',
            'media',
            'flashsaleProducts' => fn ($query) => $query
                ->whereHas('flashsale', fn ($query) => $query->current())
                ->select(['id', 'product_id', 'discount_percentage', 'stock']),
            'faqs',
            'meta',
            'wholesales',
            'resellerPrices',
        ]);

        ProductStatsService::attachSales(collect([$product]));

        $resellerId = auth('customer')->user()?->reseller_id;
        $categoryId = $product->category_id;
        $productId = $product->id;

        return Inertia::render('Products/Show', [
            'product' => ProductResource::make($product),
            'relatedProducts' => Inertia::defer(function () use ($categoryId, $productId, $resellerId) {
                $relatedProductIds = array_map('intval', CacheService::rememberManaged(
                    'product-catalog',
                    'related-product-ids:'.($categoryId ?? 'none').':'.$productId,
                    self::RELATED_PRODUCTS_CACHE_TTL,
                    fn () => Product::query()
                        ->active()
                        ->where('category_id', $categoryId)
                        ->where('id', '!=', $productId)
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->limit(self::RELATED_PRODUCTS_LIMIT)
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

                return ProductSimpleResource::collection($relatedProducts);
            }, 'relatedProducts'),
        ]);
    }
}
