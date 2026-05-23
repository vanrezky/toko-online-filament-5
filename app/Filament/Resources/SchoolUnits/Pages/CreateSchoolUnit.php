<?php

namespace App\Filament\Resources\SchoolUnits\Pages;

use App\Filament\Resources\SchoolUnits\SchoolUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolUnit extends CreateRecord
{
    protected static string $resource = SchoolUnitResource::class;

    public function getTitle(): string
    {
        return __('admin/school-unit-resource.pages.create.title');
    }
}
