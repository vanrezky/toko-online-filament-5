<?php

namespace App\Filament\Widgets;

use App\Models\InstallmentPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DueThisMonthWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $dueThisMonth = InstallmentPayment::dueThisMonth()->where('status', 'unpaid')->count();
        $dueAmount = InstallmentPayment::dueThisMonth()->where('status', 'unpaid')->sum('amount');

        return [
            Stat::make('Jatuh Tempo Bulan Ini', $dueThisMonth)
                ->description('Angsuran yang akan jatuh tempo')
                ->color('info'),
            Stat::make('Total Jatuh Tempo', 'Rp ' . number_format($dueAmount, 0, ',', '.'))
                ->color('info'),
        ];
    }
}