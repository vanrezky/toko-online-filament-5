<?php

namespace App\Filament\Resources\Flashsales\Pages;

use App\Filament\Resources\Flashsales\FlashsaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFlashsale extends CreateRecord
{
    protected static string $resource = FlashsaleResource::class;

    public function getTitle(): string
    {
        return __('admin/flashsale-resource.pages.create.title');
    }
}
