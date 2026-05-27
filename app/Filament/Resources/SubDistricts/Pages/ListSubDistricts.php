<?php

namespace App\Filament\Resources\SubDistricts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubDistricts extends ListRecords
{
    protected static string $resource = SubDistrictResource::class;

    public function getTitle(): string
    {
        return __('admin/sub-district-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
