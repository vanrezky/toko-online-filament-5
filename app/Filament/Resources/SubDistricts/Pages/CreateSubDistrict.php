<?php

namespace App\Filament\Resources\SubDistricts\Pages;

use App\Filament\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSubDistrict extends CreateRecord
{
    protected static string $resource = SubDistrictResource::class;

    public function getTitle(): string
    {
        return __('admin/sub-district-resource.pages.create.title');
    }
}
