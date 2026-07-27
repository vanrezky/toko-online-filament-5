<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSimpleResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __invoke(Request $request)
    {
        $resellerId = auth('customer')->user()?->reseller_id;

        $query = Product::query()
            ->select([
                'id', 'uuid', 'name', 'slug', 'digital', 'code',
                'stock', 'sale_price', 'price', 'min_order', 'fake_sold_count',
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

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
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
        } else {
            $query->latest();
        }

        $products = $query->paginate(12)->withQueryString();
        ProductStatsService::attachCatalogStats($products->getCollection());
        $categories = Category::active()->get();

        return Inertia::render('Products/Index', [
            'products' => ProductSimpleResource::collection($products),
            'categories' => CategoryResource::collection($categories),
            'filters' => $request->only(['category', 'search', 'sort', 'price_min', 'price_max']),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $product->loadMissing([
            'category',
            'productVariants.variantAttributes' => fn($query) => $query->with([
                'productAttribute',
                'productAttributeOption',
            ]),
            'warehouse',
            'faqs',
            'meta',
            'wholesales',
            'resellerPrices',
        ]);

        ProductStatsService::attachSales(collect([$product]));

        return Inertia::render('Products/Show', [
            'product' => ProductResource::make($product),
        ]);
    }
}
