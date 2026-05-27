<?php

namespace App\Filament\Resources\Districts\Pages;

use App\Filament\Resources\Districts\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDistrict extends CreateRecord
{
    protected static string $resource = DistrictResource::class;

    public function getTitle(): string
    {
        return __('admin/district-resource.pages.create.title');
    }
}
