<?php

namespace App\Filament\Widgets;

use App\Models\Installment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OutstandingDebtWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDebt = Installment::where('status', 'active')->sum('total_amount');
        $totalPaid = Installment::where('status', 'active')->sum('paid_amount');
        $outstanding = $totalDebt - $totalPaid;

        return [
            Stat::make('Total Piutang Aktif', 'Rp ' . number_format($outstanding, 0, ',', '.'))
                ->description('TotalOutstanding cicilan anggota')
                ->color('danger'),
            Stat::make('Total Dibayar', 'Rp ' . number_format($totalPaid, 0, ',', '.'))
                ->color('success'),
            Stat::make('Cicilan Aktif', Installment::where('status', 'active')->count())
                ->color('warning'),
        ];
    }
}