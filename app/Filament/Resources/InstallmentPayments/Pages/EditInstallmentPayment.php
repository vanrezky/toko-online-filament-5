<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentPayment extends EditRecord
{
    protected static string $resource = InstallmentPaymentResource::class;

    public function getTitle(): string
    {
        return __('admin/installment-payment-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
