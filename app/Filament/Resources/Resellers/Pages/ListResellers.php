<?php

namespace App\Filament\Resources\Resellers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Resellers\ResellerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResellers extends ListRecords
{
    protected static string $resource = ResellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
