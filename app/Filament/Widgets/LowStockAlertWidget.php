<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class LowStockAlertWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);
        
        $lowStockCount = $stats->getLowStockCount();

        $description = $lowStockCount > 0
            ? trans_choice('admin/page-dashboard.low_stock.needs_restock', $lowStockCount, ['count' => $lowStockCount])
            : __('admin/page-dashboard.low_stock.sufficient');

        return [
            Stat::make(__('admin/page-dashboard.low_stock.title'), $lowStockCount)
                ->extraAttributes([
                    'class' => 'dashboard-stat ' . ($lowStockCount > 0 ? 'dashboard-stat--stock' : 'dashboard-stat--healthy'),
                ])
                ->description($description)
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
        ];
    }
}
