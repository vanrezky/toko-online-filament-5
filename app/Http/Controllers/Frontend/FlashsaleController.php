<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashsaleResource;
use App\Http\Resources\FlashsaleProductResource;
use App\Models\Flashsale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlashsaleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $flashsale = Flashsale::query()
            ->current()
            ->withCount('products')
            ->first();

        $products = $flashsale
            ? $flashsale->products()
                ->whereHas('product', fn ($query) => $query->active())
                ->with([
                    'product.media',
                    'product.category',
                    'product.resellerPrices',
                    'product.wholesales',
                ])
                ->latest('id')
                ->paginate(16)
                ->withQueryString()
            : null;

        return Inertia::render('Flashsale/Index', [
            'flashsale' => $flashsale ? FlashsaleResource::make($flashsale) : null,
            'products' => $products ? FlashsaleProductResource::collection($products) : null,
        ]);
    }
}
