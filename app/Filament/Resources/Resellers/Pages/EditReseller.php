<?php

namespace App\Filament\Resources\Resellers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Resellers\ResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReseller extends EditRecord
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
