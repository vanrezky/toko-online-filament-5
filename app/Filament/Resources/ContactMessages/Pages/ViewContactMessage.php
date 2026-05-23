<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeFill(): void
    {
        $record = $this->getRecord();
        if (! $record->is_read) {
            $record->markAsRead();
        }
    }
}
