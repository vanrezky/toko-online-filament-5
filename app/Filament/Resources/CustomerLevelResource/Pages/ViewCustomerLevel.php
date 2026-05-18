<?php

namespace App\Filament\Resources\CustomerLevelResource\Pages;

use App\Filament\Resources\CustomerLevelResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerLevel extends ViewRecord
{
    protected static string $resource = CustomerLevelResource::class;

    public function getTitle(): string
    {
        return __('admin/customer-level-resource.pages.view.title');
    }
}