<?php

namespace App\Filament\Resources\Flashsales\Pages;

use App\Filament\Resources\Flashsales\FlashsaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlashsales extends ListRecords
{
    protected static string $resource = FlashsaleResource::class;

    public function getTitle(): string
    {
        return __('admin/flashsale-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
