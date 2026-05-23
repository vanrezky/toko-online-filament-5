<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class CustomerWidget extends ChartWidget
{
    protected ?string $heading = 'Chart';
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => [10, 20],
                    'backgroundColor' => [
                        "#1B84FF",
                        "#d1e6ff"
                    ],
                    'borderColor' => "#9BD0F5"

                ],
            ],
            'labels' => ['New Customers', 'Old Customers']
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
