<?php

namespace App\Filament\Resources\Balances\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Balances\BalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBalance extends EditRecord
{
    protected static string $resource = BalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
