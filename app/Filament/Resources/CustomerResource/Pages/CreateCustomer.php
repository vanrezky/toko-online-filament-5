<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\SchoolUnitAddressSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        app(SchoolUnitAddressSyncService::class)->syncCustomer($this->record);
    }

    public function getTitle(): string
    {
        return __('admin/customer-resource.pages.create.title');
    }
}
