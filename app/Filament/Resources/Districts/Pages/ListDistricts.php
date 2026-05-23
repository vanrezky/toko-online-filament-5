<?php

namespace App\Filament\Resources\Districts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Districts\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistricts extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
