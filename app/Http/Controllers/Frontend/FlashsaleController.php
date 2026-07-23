<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashsaleResource;
use App\Models\Flashsale;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlashsaleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($this->isEnabled(), 404);

        $flashsale = Flashsale::query()
            ->current()
            ->with([
                'products' => fn ($query) => $query->limit(12),
                'products.product.media',
                'products.product.category',
                'products.product.resellerPrices',
                'products.product.wholesales',
            ])
            ->first();

        abort_if(! $flashsale, 404);

        return Inertia::render('Flashsale/Index', [
            'flashsale' => FlashsaleResource::make($flashsale),
        ]);
    }

    private function isEnabled(): bool
    {
        return Template::query()
            ->where('code', 'flashsale')
            ->where('is_active', true)
            ->exists();
    }
}
