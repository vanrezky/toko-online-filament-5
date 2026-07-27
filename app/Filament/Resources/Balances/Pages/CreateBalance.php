<?php

namespace App\Filament\Resources\Balances\Pages;

use App\Filament\Resources\Balances\BalanceResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;
use App\Services\BalanceService;
use Illuminate\Database\Eloquent\Model;

class CreateBalance extends CreateRecord
{
    protected static string $resource = BalanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);

        return app(BalanceService::class)->topUp(
            $customer,
            (float) $data['amount'],
            $data['notes'],
            auth()->user(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
