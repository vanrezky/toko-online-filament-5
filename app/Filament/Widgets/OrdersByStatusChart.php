<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Services\DashboardStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class OrdersByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    protected ?string $heading = 'Orders by Status';

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];
        $stats = new DashboardStats($filters);
        $data = $stats->getOrdersByStatus();

        $labels = [];
        $colors = [];
        foreach (TransactionStatus::cases() as $status) {
            $labels[$status->value] = (string) $status->getLabel();
            $colors[$status->value] = match ($status->getColor()) {
                'warning' => '#f59e0b',
                'primary' => '#3b82f6',
                'info' => '#06b6d4',
                'success' => '#22c55e',
                'danger' => '#ef4444',
                default => '#9ca3af',
            };
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => array_map(fn ($status) => $colors[$status] ?? '#9ca3af', array_keys($data)),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_map(fn ($status) => $labels[$status] ?? $status, array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '65%',
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
                'tooltip' => [],
            ],
        ];
    }
}
