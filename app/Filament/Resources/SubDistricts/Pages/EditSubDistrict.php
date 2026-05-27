<?php

namespace App\Filament\Resources\SubDistricts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSubDistrict extends EditRecord
{
    protected static string $resource = SubDistrictResource::class;

    public function getTitle(): string
    {
        return __('admin/sub-district-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
