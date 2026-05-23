<?php

namespace App\Filament\Resources\Templates\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
