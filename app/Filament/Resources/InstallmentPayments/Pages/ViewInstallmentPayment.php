<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallmentPayment extends ViewRecord
{
    protected static string $resource = InstallmentPaymentResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-payment-resource.pages.view.title');
    }
}
