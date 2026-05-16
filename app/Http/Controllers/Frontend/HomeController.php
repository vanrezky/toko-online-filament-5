<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Product;
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

        // @feature-toggle: flashsale — uncomment to re-enable
        // $flashSales = Flashsale::active()
        //     ->with([
        //         'products' => fn($Q) => $Q->limit(5),
        //         'products.product.media',
        //         'products.product.category',
        //         'products.product.resellerPrices',
        //         'products.product.wholesales',
        //     ])
        //     ->first();

        $products = Product::active()->with(['media', 'category', 'resellerPrices', 'wholesales'])
            ->latest()
            ->paginate(12)->withQueryString();

        $templateData = $this->templateService->getActiveTemplateWithSections();

        return Inertia::render('Home/Index', [
            // @feature-toggle: flashsale — uncomment to re-enable
            // 'flashsales' => $flashSales ? FlashsaleResource::make($flashSales) : null,
            'flashsales' => null,
            'products' => ProductSimpleResource::collection($products),
            'filters' => $request->only(['category', 'search']),
            'template' => $templateData,
        ]);
    }
}
