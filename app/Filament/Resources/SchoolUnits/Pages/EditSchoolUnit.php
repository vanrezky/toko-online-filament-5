<?php

namespace App\Filament\Resources\SchoolUnits\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SchoolUnits\SchoolUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolUnit extends EditRecord
{
    protected static string $resource = SchoolUnitResource::class;

    public function getTitle(): string
    {
        return __('admin/school-unit-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
