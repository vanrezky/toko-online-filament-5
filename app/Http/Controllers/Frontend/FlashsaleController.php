<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashsaleProductResource;
use App\Http\Resources\FlashsaleResource;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlashsaleController extends Controller
{
    public function __construct(private readonly CatalogService $catalogService) {}

    public function __invoke(Request $request): Response
    {
        $flashsale = $this->catalogService->currentFlashsale();

        $products = $flashsale
            ? $this->catalogService->flashsaleProducts($flashsale)
            : null;

        return Inertia::render('Flashsale/Index', [
            'flashsale' => $flashsale ? FlashsaleResource::make($flashsale) : null,
            'products' => $products ? FlashsaleProductResource::collection($products) : null,
        ]);
    }
}
