<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const PAGE_SIZES = [12, 24, 36];

    private const VARIANT_ATTRIBUTE_ALIASES = [
        'color' => ['Warna', 'Color'],
        'size' => ['Ukuran', 'Size'],
        'gender' => ['Gender'],
    ];

    public function __construct(private readonly CatalogService $catalogService) {}

    public function __invoke(Request $request): Response
    {
        $filters = $this->normalizeFilters($request);
        $resellerId = auth('customer')->user()?->reseller_id;
        $products = $this->catalogService->paginateProducts($filters, $resellerId);
        $categories = $this->catalogService->activeCategories();

        return Inertia::render('Products/Index', [
            'products' => ProductSimpleResource::collection($products),
            'categories' => CategoryResource::collection($categories),
            'filters' => $filters['query'],
        ]);
    }

    /** @return array{search: string, categories: list<string>, sort: string, price_min: ?float, price_max: ?float, rating_min: ?float, promos: list<string>, per_page: int, variants: array<string, list<string>>, query: array<string, string|list<string>>} */
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

    public function show(Request $request, Product $product): Response
    {
        $product = $this->catalogService->productDetail($product);
        $resellerId = auth('customer')->user()?->reseller_id;

        return Inertia::render('Products/Show', [
            'product' => ProductResource::make($product),
            'relatedProducts' => Inertia::defer(
                fn () => ProductSimpleResource::collection($this->catalogService->relatedProducts($product, $resellerId)),
                'relatedProducts',
            ),
        ]);
    }
}
