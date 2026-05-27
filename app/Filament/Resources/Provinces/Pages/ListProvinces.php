<?php

namespace App\Filament\Resources\Provinces\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    public function getTitle(): string
    {
        return __('admin/province-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
