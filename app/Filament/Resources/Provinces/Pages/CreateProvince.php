<?php

namespace App\Filament\Resources\Provinces\Pages;

use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProvince extends CreateRecord
{
    protected static string $resource = ProvinceResource::class;

    public function getTitle(): string
    {
        return __('admin/province-resource.pages.create.title');
    }
}
