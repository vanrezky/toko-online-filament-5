<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string
    {
        return __('admin/product-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')->label(__('admin/product-resource.fields.back'))->color('warning')->url($this->getResource()::getUrl('index')),
            Actions\DeleteAction::make(),
        ];
    }
}
