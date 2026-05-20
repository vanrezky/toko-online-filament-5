<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Installment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditUtilizationWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $levels = CustomerLevel::withCount('customers')->get();

        return $levels->map(function ($level) {
            $customers = $level->customers;
            $totalLimit = $customers->sum(fn($c) => $c->effective_credit_limit);
            $totalOutstanding = $customers->sum(fn($c) => $c->outstanding_balance);
            $utilization = $totalLimit > 0 ? ($totalOutstanding / $totalLimit) * 100 : 0;

            return Stat::make($level->name, number_format($utilization, 1) . '%')
                ->description('Pemanfaatan kredit')
                ->color($utilization > 80 ? 'danger' : ($utilization > 50 ? 'warning' : 'success'));
        })->toArray();
    }
}