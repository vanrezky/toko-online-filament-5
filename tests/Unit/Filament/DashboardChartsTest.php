<?php

namespace Tests\Unit\Filament;

use App\Filament\Widgets\OrdersByStatusChart;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopProductsChart;
use ReflectionMethod;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    public function test_dashboard_chart_options_are_compatible_with_chart_js(): void
    {
        $salesOptions = $this->invokeOptions(SalesTrendChart::class);
        $topProductsOptions = $this->invokeOptions(TopProductsChart::class);

        $this->assertSame(['display' => false], $salesOptions['scales']['y']['ticks']);
        $this->assertSame(['display' => true], $topProductsOptions['scales']['x']['ticks']);
    }

    public function test_order_status_chart_is_horizontal(): void
    {
        $type = new ReflectionMethod(OrdersByStatusChart::class, 'getType');
        $type->setAccessible(true);
        $options = $this->invokeOptions(OrdersByStatusChart::class);

        $this->assertSame('bar', $type->invoke(new OrdersByStatusChart));
        $this->assertSame('y', $options['indexAxis']);
    }

    private function invokeOptions(string $widget): array
    {
        $method = new ReflectionMethod($widget, 'getOptions');
        $method->setAccessible(true);

        return $method->invoke(new $widget);
    }
}
