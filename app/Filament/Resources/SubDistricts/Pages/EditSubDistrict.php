<?php

namespace App\Filament\Resources\SubDistricts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubDistrict extends EditRecord
{
    protected static string $resource = SubDistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
