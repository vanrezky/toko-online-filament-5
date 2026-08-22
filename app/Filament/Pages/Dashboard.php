<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OrdersByStatusChart;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\SalesStatsOverviewWidget;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopProductsChart;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $routePath = 'dashboard';

    public function getTitle(): string
    {
        return __('admin/page-dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/page-dashboard.filters.title'))
                    ->description(__('admin/page-dashboard.filters.description'))
                    ->extraAttributes(['class' => 'dashboard-filter-panel'])
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('admin/page-dashboard.filters.start_date'))
                            ->placeholder(__('admin/page-dashboard.filters.select_date'))
                            ->default(now()->subDays(6)->toDateString())
                            ->rule('date')
                            ->rule('before_or_equal:endDate')
                            ->displayFormat('d M Y'),
                        DatePicker::make('endDate')
                            ->label(__('admin/page-dashboard.filters.end_date'))
                            ->placeholder(__('admin/page-dashboard.filters.select_date'))
                            ->default(now()->toDateString())
                            ->rule('date')
                            ->rule('after_or_equal:startDate')
                            ->displayFormat('d M Y'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpanFull(),
            ])->columns(1);
    }

    public function getWidgets(): array
    {
        return [
            SalesStatsOverviewWidget::class,
            SalesTrendChart::class,
            TopProductsChart::class,
            OrdersByStatusChart::class,
            RecentOrdersTable::class,
            LowStockAlertWidget::class,
        ];
    }
}
