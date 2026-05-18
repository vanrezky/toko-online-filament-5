<?php

namespace App\Filament\Resources\InstallmentResource\Pages;

use App\Filament\Resources\InstallmentResource;
use Filament\Resources\Pages\ListRecords;

class ListInstallments extends ListRecords
{
    protected static string $resource = InstallmentResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-resource.pages.list.title');
    }
}