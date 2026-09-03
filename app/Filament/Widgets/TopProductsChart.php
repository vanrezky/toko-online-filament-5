<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStats;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TopProductsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    protected ?string $maxHeight = '600px';

    public function getHeading(): string
    {
        return __('admin/page-dashboard.top_products.title');
    }

    public ?string $filter = 'quantity';

    protected function getData(): array
    {
        $stats = new DashboardStats($this->pageFilters ?? []);
        $products = $stats->getTopProducts(10);

        $isQuantity = ($this->filter ?? 'quantity') === 'quantity';

        return [
            'datasets' => [
                [
                    'label' => $isQuantity
                        ? __('admin/page-dashboard.top_products.quantity_sold')
                        : __('admin/page-dashboard.top_products.revenue'),
                    'data' => array_column($products, $isQuantity ? 'total_quantity' : 'total_revenue'),
                    'backgroundColor' => 'rgba(37, 99, 235, 0.8)',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => array_column($products, 'name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => false,
                        'boxWidth' => 24,
                        'padding' => 16,
                    ],
                ],
                'tooltip' => [],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['display' => true],
                ],
                'y' => [
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(148, 163, 184, 0.35)',
                    ],
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'quantity' => __('admin/page-dashboard.top_products.by_quantity'),
            'revenue' => __('admin/page-dashboard.top_products.by_revenue'),
        ];
    }
}
