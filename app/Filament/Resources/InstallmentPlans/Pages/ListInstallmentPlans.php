<?php

namespace App\Filament\Resources\InstallmentPlans\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InstallmentPlans\InstallmentPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstallmentPlans extends ListRecords
{
    protected static string $resource = InstallmentPlanResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-plan-resource.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}