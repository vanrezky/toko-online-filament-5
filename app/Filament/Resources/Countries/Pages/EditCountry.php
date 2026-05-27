<?php

namespace App\Filament\Resources\Countries\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    public function getTitle(): string
    {
        return __('admin/country-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return notification(__('admin/country-resource.notifications.updated'));
    }
}
