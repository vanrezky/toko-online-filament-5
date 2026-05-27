<?php

namespace App\Filament\Resources\Transactions\Widgets;

use App\Enums\TransactionStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;
use App\Models\Transaction;

class TransactionStatsOverview extends BaseWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public ?string $activeTab = null;

    protected function getStats(): array
    {
        $activeTab = $this->activeTab ?? 'all';
        $query = Transaction::query();
        if ($activeTab !== 'all') {
            $query->where('status', $activeTab);
        }

        $totalOrders = (clone $query)->count();

        $packedOrders = 0;
        if (in_array($activeTab, ['all', TransactionStatus::packed->value], true)) {
            $packedOrders = $activeTab === TransactionStatus::packed->value
                ? $totalOrders
                : Transaction::query()->where('status', TransactionStatus::packed->value)->count();
        }

        $ordersLast7Days = (clone $query)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $avgOrdersPerDay = $ordersLast7Days / 7;

        return [
            Stat::make('Total Orders', number_format($totalOrders))
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),

            Stat::make('Order Baru (Packed)', number_format($packedOrders))
                ->icon('heroicon-o-archive-box')
                ->color($packedOrders > 0 ? 'warning' : 'gray'),

            Stat::make('Rata-rata Order / Hari', number_format($avgOrdersPerDay, 1))
                ->icon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }
}
