<?php

namespace App\Filament\Resources\SchoolUnits\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SchoolUnits\SchoolUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolUnits extends ListRecords
{
    protected static string $resource = SchoolUnitResource::class;

    public function getTitle(): string
    {
        return __('admin/school-unit-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
