<?php

namespace App\Filament\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use App\Constants\Status;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return __('admin/product-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()->label(__('admin/product-resource.tabs_list.all')),
            'low_stock' => Tab::make()
                ->label(__('admin/product-resource.tabs_list.low_stock'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'security_stock')->where('stock', '>', Status::COUNT_OUT_OF_STOCK))
                ->badge(Product::query()->whereColumn('stock', '<=', 'security_stock')->where('stock', '>', Status::COUNT_OUT_OF_STOCK)->count()),
            'out_of_stock' => Tab::make()
                ->label(__('admin/product-resource.tabs_list.out_of_stock'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('stock', Status::COUNT_OUT_OF_STOCK))
                ->badge(Product::query()->where('stock', Status::COUNT_OUT_OF_STOCK)->count())
                ->badgeColor('danger'),

        ];
    }
}
