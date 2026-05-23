<?php

namespace App\Filament\Resources\Templates\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
