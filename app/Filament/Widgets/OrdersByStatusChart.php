<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Services\DashboardStats;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    private const STATUS_COLORS = [
        'warning' => '#f59e0b',
        'primary' => '#4B49AC',
        'info' => '#3490DC',
        'success' => '#22c55e',
        'danger' => '#F3797E',
        'default' => '#9ca3af',
    ];

    public function getHeading(): string
    {
        return __('admin/page-dashboard.orders_by_status.title');
    }

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);
        $data = $stats->getOrdersByStatus();

        $labels = [];
        $colors = [];
        foreach (TransactionStatus::cases() as $status) {
            $labels[$status->value] = (string) $status->getLabel();
            $colors[$status->value] = self::STATUS_COLORS[$status->getColor()] ?? self::STATUS_COLORS['default'];
        }

        return [
            'datasets' => [
                [
                    'label' => __('admin/page-dashboard.orders_by_status.order_count'),
                    'data' => array_values($data),
                    'backgroundColor' => array_map(fn ($status) => $colors[$status] ?? self::STATUS_COLORS['default'], array_keys($data)),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_map(fn ($status) => $labels[$status] ?? $status, array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 10,
                        'padding' => 12,
                    ],
                ],
                'tooltip' => [],
            ],
        ];
    }
}
