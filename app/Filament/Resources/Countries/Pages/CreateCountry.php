<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;

    public function getTitle(): string
    {
        return __('admin/country-resource.pages.create.title');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return notification(__('admin/country-resource.notifications.created'));
    }
}
