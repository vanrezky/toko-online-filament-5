<?php

namespace App\Filament\Resources\CustomerLevelResource\Pages;

use App\Filament\Resources\CustomerLevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerLevel extends CreateRecord
{
    protected static string $resource = CustomerLevelResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-level-resource.pages.create.title');
    }
}