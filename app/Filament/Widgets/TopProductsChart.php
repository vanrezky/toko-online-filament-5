<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStats;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

class TopProductsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    public function getHeading(): string
    {
        return __('admin/page-dashboard.top_products.title');
    }

    public ?string $filter = 'quantity';

    protected function getData(): array
    {
        $stats = new DashboardStats($this->pageFilters ?? []);
        $products = $stats->getTopProducts(5);

        $isQuantity = ($this->filter ?? 'quantity') === 'quantity';

        return [
            'datasets' => [
                [
                    'label' => $isQuantity
                        ? __('admin/page-dashboard.top_products.quantity_sold')
                        : __('admin/page-dashboard.top_products.revenue'),
                    'data' => array_column($products, $isQuantity ? 'total_quantity' : 'total_revenue'),
                    'backgroundColor' => [
                        'rgba(37, 99, 235, 0.8)',
                        'rgba(37, 99, 235, 0.6)',
                        'rgba(37, 99, 235, 0.5)',
                        'rgba(37, 99, 235, 0.4)',
                        'rgba(37, 99, 235, 0.3)',
                    ],
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
                'legend' => ['display' => false],
                'tooltip' => [],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => [],
                ],
                'y' => [
                    'grid' => ['display' => false],
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
