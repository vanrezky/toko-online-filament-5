<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductImageUrlImporter;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<int, array{url?: string}> */
    protected array $imageUrls = [];

    public function getTitle(): string
    {
        return __('admin/product-resource.pages.create.title');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->imageUrls = $data['image_urls'] ?? [];
        unset($data['image_urls']);

        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ProductImageUrlImporter::class)->import($this->getRecord(), $this->imageUrls);
    }
}
