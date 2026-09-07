<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FlashsaleResource;
use App\Http\Resources\ProductSimpleResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\TemplateResource;
use App\Models\Category;
use App\Models\Flashsale;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Template;
use App\Models\TemplateSection;
use App\Services\CacheService;
use App\Services\ProductStatsService;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function index(Request $request)
    {
        if ($request->filled('search')) {
            return redirect()->route('frontend.products', $request->only('search'));
        }

        if ($request->filled('category')) {
            return redirect()->route('frontend.products', $request->only('category'));
        }

        return $this->renderHome($request, $this->templateService->getActiveTemplate());
    }

    public function preview(Request $request, Template $template)
    {
        $this->authorize('view', $template);

        return $this->renderHome($request, $this->templateService->getPreviewTemplate($template), true);
    }

    private function renderHome(Request $request, ?Template $template, bool $isPreview = false)
    {
        $activeSections = $template?->sections->where('is_active', true) ?? collect();
        $configuredSectionTypes = $activeSections->pluck('type')->all();
        $hasTemplateSections = $template?->sections->isNotEmpty() ?? false;
        $usesLegacySections = ! $hasTemplateSections;
        $isFlashsaleEnabled = $usesLegacySections || in_array(TemplateSection::TYPE_FLASH_SALE, $configuredSectionTypes, true);
        $featuredSection = $activeSections->firstWhere('type', TemplateSection::TYPE_FEATURED_PRODUCTS);
        $productsGridSection = $activeSections->firstWhere('type', TemplateSection::TYPE_PRODUCTS_GRID);
        $flashsaleSection = $activeSections->firstWhere('type', TemplateSection::TYPE_FLASH_SALE);
        $sectionContent = fn (?TemplateSection $section, string $key, mixed $default = null) => $section
            ? $this->templateService->getSectionContent($section, $key, $default)
            : $default;
        $productsLimit = max(
            1,
            (int) $sectionContent($featuredSection, 'limit', 6),
            (int) $sectionContent($productsGridSection, 'limit', 10),
        );
        $productsCategoryId = (int) $sectionContent($productsGridSection, 'category_id', 0);
        $flashsaleLimit = max(1, (int) $sectionContent($flashsaleSection, 'limit', 5));
        $needsProducts = $usesLegacySections || in_array(TemplateSection::TYPE_FEATURED_PRODUCTS, $configuredSectionTypes, true)
            || in_array(TemplateSection::TYPE_PRODUCTS_GRID, $configuredSectionTypes, true)
            || $isFlashsaleEnabled;
        $needsCategories = $usesLegacySections || in_array(TemplateSection::TYPE_CATEGORY_MENU, $configuredSectionTypes, true);
        // The registry keeps the existing carousel as a compatibility section
        // until it is represented by an admin-managed section type.
        $needsSliders = true;
        $resellerId = auth('customer')->user()?->reseller_id;

        $flashsale = null;

        if ($isFlashsaleEnabled) {
            $flashsale = Flashsale::query()
                ->current()
                ->with([
                    'products' => fn ($query) => $query
                        ->select(['id', 'flashsale_id', 'product_id', 'discount_percentage', 'stock'])
                        ->limit($flashsaleLimit),
                    'products.product' => fn ($query) => $query->select([
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
                    'products.product.flashsaleProducts' => fn ($query) => $query
                        ->whereHas('flashsale', fn ($query) => $query->current())
                        ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                    'products.product.wholesales' => fn ($query) => $query
                        ->where('min_qty', '<=', 1)
                        ->select(['id', 'product_id', 'min_qty', 'price']),
                ])
                ->when($productsCategoryId > 0, fn ($query) => $query->where('category_id', $productsCategoryId))
                ->when($resellerId, fn ($query) => $query->with([
                    'products.product.resellerPrices' => fn ($query) => $query
                        ->where('reseller_id', $resellerId)
                        ->select(['id', 'product_id', 'reseller_id', 'price']),
                ]))
                ->first();

            if ($flashsale) {
                ProductStatsService::attachCatalogStats($flashsale->products->pluck('product')->filter()->values());
            }
        }

        $products = collect();

        if ($needsProducts) {
            $products = Product::query()
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
                ->latest()
                ->simplePaginate($productsLimit)
                ->withQueryString();

            ProductStatsService::attachCatalogStats($products->getCollection());
        }

        $sliders = $needsSliders
            ? CacheService::rememberManaged(
                'frontend',
                Slider::CACHE_KEY,
                Slider::CACHE_TTL,
                fn () => Slider::query()
                    ->visible()
                    ->ordered()
                    ->with('media')
                    ->get(),
            )
            : collect();

        return Inertia::render('Home/Index', [
            'categories' => $needsCategories ? function () {
                return CacheService::rememberManaged('frontend', 'frontend_categories', 3600, function () {
                    return CategoryResource::collection(
                        Category::homepage()->with('media')->get()
                    );
                });
            } : [],
            'products' => ProductSimpleResource::collection($products),
            'filters' => $request->only(['category', 'search']),
            'template' => $template ? TemplateResource::make($template) : null,
            'colorScheme' => $this->templateService->normalizeColorScheme($template?->color_scheme),
            'sliders' => SliderResource::collection($sliders),
            'flashsales' => $flashsale ? FlashsaleResource::make($flashsale) : null,
            'templatePreview' => $isPreview,
        ]);
    }
}
