<?php

namespace App\Filament\Resources\Districts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Districts\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    public function getTitle(): string
    {
        return __('admin/district-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
