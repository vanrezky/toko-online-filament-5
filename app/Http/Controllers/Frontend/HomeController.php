<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashsaleResource;
use App\Http\Resources\ProductSimpleResource;
use App\Http\Resources\SliderResource;
use App\Http\Resources\TemplateResource;
use App\Models\Flashsale;
use App\Models\Product;
use App\Models\Slider;
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

        $template = $this->templateService->getActiveTemplate();
        $isFlashsaleEnabled = $template?->sections->contains('type', TemplateSection::TYPE_FLASH_SALE) ?? false;
        $resellerId = auth('customer')->user()?->reseller_id;

        $flashsale = null;

        if ($isFlashsaleEnabled) {
            $flashsale = Flashsale::query()
                ->current()
                ->with([
                    'products' => fn ($query) => $query
                        ->select(['id', 'flashsale_id', 'product_id', 'discount_percentage', 'stock'])
                        ->limit(5),
                    'products.product' => fn ($query) => $query->select([
                        'id', 'uuid', 'name', 'slug', 'digital', 'code',
                        'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count',
                    ]),
                    'products.product.media',
                    'products.product.flashsaleProducts' => fn ($query) => $query
                        ->whereHas('flashsale', fn ($query) => $query->current())
                        ->select(['id', 'product_id', 'discount_percentage', 'stock']),
                    'products.product.wholesales' => fn ($query) => $query
                        ->where('min_qty', '<=', 1)
                        ->select(['id', 'product_id', 'min_qty', 'price']),
                ])
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
            ->simplePaginate(10)
            ->withQueryString();

        ProductStatsService::attachCatalogStats($products->getCollection());

        $sliders = CacheService::rememberManaged(
            'frontend',
            Slider::CACHE_KEY,
            Slider::CACHE_TTL,
            fn () => Slider::query()
                ->visible()
                ->ordered()
                ->with('media')
                ->get(),
        );

        return Inertia::render('Home/Index', [
            'products' => ProductSimpleResource::collection($products),
            'filters' => $request->only(['category', 'search']),
            'template' => $template ? TemplateResource::make($template) : null,
            'sliders' => SliderResource::collection($sliders),
            'flashsales' => $flashsale ? FlashsaleResource::make($flashsale) : null,
        ]);
    }
}
