<?php

namespace App\Filament\Resources\TemplateSections\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TemplateSections\TemplateSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplateSection extends EditRecord
{
    protected static string $resource = TemplateSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
