<?php

namespace App\Filament\Resources\CustomerLevelResource\Pages;

use App\Filament\Resources\CustomerLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerLevel extends EditRecord
{
    protected static string $resource = CustomerLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}