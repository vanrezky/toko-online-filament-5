<?php

namespace App\Filament\Resources\Flashsales\Pages;

use App\Filament\Resources\Flashsales\FlashsaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlashsale extends EditRecord
{
    protected static string $resource = FlashsaleResource::class;

    public function getTitle(): string
    {
        return __('admin/flashsale-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
