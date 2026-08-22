<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    public function getHeading(): string
    {
        return __('admin/page-dashboard.sales_trend.title');
    }

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);
        $trend = $stats->getSalesTrend();

        return [
            'datasets' => [
                [
                    'label' => __('admin/page-dashboard.sales_trend.current_period'),
                    'data' => $trend['current'],
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                    'pointHitRadius' => 8,
                ],
                [
                    'label' => __('admin/page-dashboard.sales_trend.previous_period'),
                    'data' => $trend['previous'],
                    'borderColor' => '#94a3b8',
                    'backgroundColor' => 'rgba(148, 163, 184, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
                    'pointRadius' => 3,
                    'pointHoverRadius' => 5,
                    'pointHitRadius' => 8,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => false,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['maxRotation' => 45],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0, 0, 0, 0.05)'],
                    'ticks' => [],
                ],
            ],
        ];
    }
}
