<?php

namespace App\Filament\Resources\InstallmentPlans\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Resources\InstallmentPlans\InstallmentPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentPlan extends EditRecord
{
    protected static string $resource = InstallmentPlanResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-plan-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}