<?php

namespace App\Filament\Resources\CustomerLevels\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CustomerLevels\CustomerLevelResource;
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
            CreateAction::make(),
        ];
    }
}