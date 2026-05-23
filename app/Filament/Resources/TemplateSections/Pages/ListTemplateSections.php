<?php

namespace App\Filament\Resources\TemplateSections\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TemplateSections\TemplateSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplateSections extends ListRecords
{
    protected static string $resource = TemplateSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
