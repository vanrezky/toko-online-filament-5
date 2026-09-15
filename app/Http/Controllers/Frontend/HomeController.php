<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FlashsaleResource;
use App\Http\Resources\ProductSimpleResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Models\TemplateSection;
use App\Services\CatalogService;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly CatalogService $catalogService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->filled('search')) {
            return redirect()->route('frontend.products', $request->only('search'));
        }

        if ($request->filled('category')) {
            return redirect()->route('frontend.products', $request->only('category'));
        }

        return $this->renderHome($request, $this->templateService->getActiveTemplate());
    }

    public function preview(Request $request, Template $template): Response
    {
        $this->authorize('view', $template);

        return $this->renderHome($request, $this->templateService->getPreviewTemplate($template), true);
    }

    private function renderHome(Request $request, ?Template $template, bool $isPreview = false): Response
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
        $productLimits = [];

        if ($usesLegacySections || $featuredSection) {
            $productLimits[] = (int) $sectionContent($featuredSection, 'limit', 6);
        }

        if ($usesLegacySections || $productsGridSection) {
            $productLimits[] = (int) $sectionContent($productsGridSection, 'limit', 10);
        }

        $productsLimit = max(1, ...($productLimits ?: [1]));
        $productsCategoryId = (int) $sectionContent($productsGridSection, 'category_id', 0);
        $flashsaleLimit = max(1, (int) $sectionContent($flashsaleSection, 'limit', 5));
        $needsProducts = $usesLegacySections
            || in_array(TemplateSection::TYPE_FEATURED_PRODUCTS, $configuredSectionTypes, true)
            || in_array(TemplateSection::TYPE_PRODUCTS_GRID, $configuredSectionTypes, true);
        $needsCategories = $usesLegacySections || in_array(TemplateSection::TYPE_CATEGORY_MENU, $configuredSectionTypes, true);
        // Only load slider data when the template renders the carousel section.
        $needsSliders = $usesLegacySections
            || in_array(TemplateSection::TYPE_HERO_CAROUSEL, $configuredSectionTypes, true);
        $resellerId = auth('customer')->user()?->reseller_id;

        $flashsales = $isFlashsaleEnabled
            ? Inertia::defer(function () use ($flashsaleLimit, $resellerId) {
                $flashsale = $this->catalogService->homepageFlashsale($flashsaleLimit, $resellerId);

                return $flashsale ? FlashsaleResource::make($flashsale) : null;
            }, 'flashsales')
            : null;

        $products = $needsProducts
            ? Inertia::defer(function () use ($productsCategoryId, $productsLimit, $resellerId) {
                $products = $this->catalogService->homepageProducts($productsCategoryId, $productsLimit, $resellerId);

                return ProductSimpleResource::collection($products);
            }, 'products')
            : ProductSimpleResource::collection(collect());

        $categories = $needsCategories
            ? Inertia::defer(function () {
                return CategoryResource::collection($this->catalogService->homepageCategories());
            }, 'categories')
            : [];

        $sliders = $needsSliders
            ? Inertia::defer(function () {
                return SliderResource::collection($this->catalogService->visibleSliders());
            }, 'sliders')
            : SliderResource::collection(collect());

        return Inertia::render('Home/Index', [
            'categories' => $categories,
            'products' => $products,
            'filters' => $request->only(['category', 'search']),
            'template' => $template ? TemplateResource::make($template) : null,
            'colorScheme' => $this->templateService->normalizeColorScheme($template?->color_scheme),
            'sliders' => $sliders,
            'flashsales' => $flashsales,
            'templatePreview' => $isPreview,
        ]);
    }
}
