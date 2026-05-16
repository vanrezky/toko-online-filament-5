<?php

namespace App\Filament\Widgets;

use App\Models\Installment;
use App\Models\InstallmentPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverdueInstallmentsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $overduePayments = InstallmentPayment::overdue()->count();
        $overdueAmount = InstallmentPayment::overdue()->sum('amount');

        return [
            Stat::make('Angsuran Terlambat', $overduePayments)
                ->description('Belum dibayar melebihi jatuh tempo')
                ->color('danger'),
            Stat::make('Total Tunggakan', 'Rp ' . number_format($overdueAmount, 0, ',', '.'))
                ->color('danger'),
            Stat::make('Cicilan Bermasalah', Installment::where('status', 'overdue')->count())
                ->color('warning'),
        ];
    }
}