<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class SalesStatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;
    
    protected static bool $isDiscovered = true;

    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);

        $revenueStats = $stats->getRevenueStats();
        $ordersStats = $stats->getOrdersStats();
        $customersStats = $stats->getNewCustomersStats();
        $aovStats = $stats->getAverageOrderValueStats();

        return [
            Stat::make(__('admin/page-dashboard.stats.revenue'), toMoney($revenueStats['value']))
                ->extraAttributes(['class' => 'dashboard-stat dashboard-stat--revenue'])
                ->description($this->formatPercentChange($revenueStats['percent_change']))
                ->descriptionIcon($revenueStats['percent_change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getMiniChart())
                ->color($revenueStats['percent_change'] >= 0 ? 'success' : 'danger'),

            Stat::make(__('admin/page-dashboard.stats.orders'), number_format($ordersStats['value']))
                ->extraAttributes(['class' => 'dashboard-stat dashboard-stat--orders'])
                ->description($this->formatPercentChange($ordersStats['percent_change']))
                ->descriptionIcon($ordersStats['percent_change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ordersStats['percent_change'] >= 0 ? 'success' : 'danger'),

            Stat::make(__('admin/page-dashboard.stats.new_customers'), number_format($customersStats['value']))
                ->extraAttributes(['class' => 'dashboard-stat dashboard-stat--customers'])
                ->description($this->formatPercentChange($customersStats['percent_change']))
                ->descriptionIcon($customersStats['percent_change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($customersStats['percent_change'] >= 0 ? 'success' : 'danger'),

            Stat::make(__('admin/page-dashboard.stats.average_order_value'), toMoney($aovStats['value']))
                ->extraAttributes(['class' => 'dashboard-stat dashboard-stat--value'])
                ->description($this->formatPercentChange($aovStats['percent_change']))
                ->descriptionIcon($aovStats['percent_change'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($aovStats['percent_change'] >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getMiniChart(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);
        $trend = $stats->getSalesTrend();
        
        return array_slice($trend['current'], -7);
    }

    protected function formatPercentChange(float $percent): string
    {
        $sign = $percent >= 0 ? '+' : '';
        return __('admin/page-dashboard.stats.change_from_previous', [
            'percentage' => $sign . $percent . '%',
        ]);
    }
}
