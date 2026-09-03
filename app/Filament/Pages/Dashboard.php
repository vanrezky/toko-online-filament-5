<?php

namespace App\Filament\Pages;

use App\Enums\TransactionStatus;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OrdersByStatusChart;
use App\Filament\Widgets\RecentOrdersTable;
use App\Filament\Widgets\SalesStatsOverviewWidget;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopProductsChart;
use App\Models\Category;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $routePath = 'dashboard';

    public function getTitle(): string
    {
        return __('admin/page-dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
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
                        Select::make('categoryId')
                            ->label(__('admin/page-dashboard.filters.category'))
                            ->placeholder(__('admin/page-dashboard.filters.all_categories'))
                            ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Select::make('transactionStatus')
                            ->label(__('admin/page-dashboard.filters.status'))
                            ->placeholder(__('admin/page-dashboard.filters.all_statuses'))
                            ->options(TransactionStatus::class),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
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
