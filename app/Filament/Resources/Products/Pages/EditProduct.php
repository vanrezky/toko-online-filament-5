<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductImageUrlImporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<int, array{url?: string}> */
    protected array $imageUrls = [];

    public function getTitle(): string
    {
        return __('admin/product-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label(__('admin/product-resource.fields.back'))->color('warning')->url($this->getResource()::getUrl('index')),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->imageUrls = $data['image_urls'] ?? [];
        unset($data['image_urls']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(ProductImageUrlImporter::class)->import($this->getRecord(), $this->imageUrls);
    }
}
