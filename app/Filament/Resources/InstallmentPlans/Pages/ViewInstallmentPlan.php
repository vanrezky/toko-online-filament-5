<?php

namespace App\Filament\Resources\InstallmentPlans\Pages;

use App\Filament\Resources\InstallmentPlans\InstallmentPlanResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallmentPlan extends ViewRecord
{
    protected static string $resource = InstallmentPlanResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-plan-resource.pages.view.title');
    }
}