<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return __('admin/product-resource.pages.view.title');
    }

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing([
            'category',
            'warehouse',
            'meta',
            'resellerPrices.reseller',
            'resellerPrices.wholesales',
            'wholesales',
            'faqs',
            'adminReviews.media',
            'media',
        ]);
    }
}
