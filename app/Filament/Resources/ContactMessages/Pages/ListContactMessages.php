<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTitle(): string
    {
        return __('admin/contact-message-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
