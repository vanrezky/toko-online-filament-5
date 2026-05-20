<?php

namespace App\Filament\Resources\CustomerLevelResource\Pages;

use App\Filament\Resources\CustomerLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLevels extends ListRecords
{
    protected static string $resource = CustomerLevelResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-level-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}