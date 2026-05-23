<?php

namespace App\Filament\Resources\CustomerLevels\Pages;

use App\Filament\Resources\CustomerLevels\CustomerLevelResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerLevel extends ViewRecord
{
    protected static string $resource = CustomerLevelResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-level-resource.pages.view.title');
    }
}