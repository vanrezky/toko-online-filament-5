<?php

namespace App\Filament\Resources\Provinces\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvince extends EditRecord
{
    protected static string $resource = ProvinceResource::class;

    public function getTitle(): string
    {
        return __('admin/province-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
