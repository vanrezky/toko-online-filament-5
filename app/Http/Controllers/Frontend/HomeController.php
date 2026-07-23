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
use App\Services\TemplateService;
use App\Services\ProductStatsService;
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

        $flashsale = null;

        if ($isFlashsaleEnabled) {
            $flashsale = Flashsale::query()
                ->current()
                ->with([
                    'products' => fn($query) => $query->limit(5),
                    'products.product.media',
                    'products.product.category',
                    'products.product.resellerPrices',
                    'products.product.wholesales',
                ])
                ->first();

            if ($flashsale) {
                ProductStatsService::attachCatalogStats($flashsale->products->pluck('product')->filter()->values());
            }
        }

        $resellerId = auth('customer')->user()?->reseller_id;

        $products = Product::query()
            ->select([
                'id',
                'uuid',
                'name',
                'slug',
                'category_id',
                'digital',
                'description',
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
                'category:id,name',
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

        $sliders = CacheService::remember(
            Slider::CACHE_KEY,
            Slider::CACHE_TTL,
            fn() => Slider::query()
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
