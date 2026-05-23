<?php

namespace App\Filament\Resources\SubDistricts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubDistricts extends ListRecords
{
    protected static string $resource = SubDistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
