<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Storefront')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('frontend.template-preview', ['template' => $this->record->uuid]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
