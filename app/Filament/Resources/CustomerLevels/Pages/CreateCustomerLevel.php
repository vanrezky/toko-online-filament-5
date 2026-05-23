<?php

namespace App\Filament\Resources\CustomerLevels\Pages;

use App\Filament\Resources\CustomerLevels\CustomerLevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerLevel extends CreateRecord
{
    protected static string $resource = CustomerLevelResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-level-resource.pages.create.title');
    }
}