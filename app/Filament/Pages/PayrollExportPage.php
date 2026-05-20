<?php

namespace App\Filament\Pages;

use App\Models\CustomerLevel;
use App\Services\PayrollExportService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class PayrollExportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.payroll-export';

    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;
    public ?int $selectedLevelId = null;
    public array $previewData = [];

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function getTitle(): string
    {
        return __('admin/payroll-export-page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/payroll-export-page.navigation_label');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('admin/payroll-export-page.actions.preview'))
                ->action('previewData'),
            Action::make('exportExcel')
                ->label(__('admin/payroll-export-page.actions.export_excel'))
                ->action('exportExcel')
                ->color('success'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('selectedMonth')
                        ->label(__('admin/payroll-export-page.fields.month'))
                        ->options([
                            1 => __('admin/payroll-export-page.months.january'),
                            2 => __('admin/payroll-export-page.months.february'),
                            3 => __('admin/payroll-export-page.months.march'),
                            4 => __('admin/payroll-export-page.months.april'),
                            5 => __('admin/payroll-export-page.months.may'),
                            6 => __('admin/payroll-export-page.months.june'),
                            7 => __('admin/payroll-export-page.months.july'),
                            8 => __('admin/payroll-export-page.months.august'),
                            9 => __('admin/payroll-export-page.months.september'),
                            10 => __('admin/payroll-export-page.months.october'),
                            11 => __('admin/payroll-export-page.months.november'),
                            12 => __('admin/payroll-export-page.months.december'),
                        ])
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\Select::make('selectedYear')
                        ->label(__('admin/payroll-export-page.fields.year'))
                        ->options(array_combine(range(now()->year - 5, now()->year + 1), range(now()->year - 5, now()->year + 1)))
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\Select::make('selectedLevelId')
                        ->label(__('admin/payroll-export-page.fields.customer_level'))
                        ->options(CustomerLevel::query()->pluck('name', 'id'))
                        ->columnSpan(1)
                        ->nullable()
                        ->searchable(),
                ]),
        ]);
    }

    public function previewData(): void
    {
        $service = app(PayrollExportService::class);
        $this->previewData = $service->getPayrollSummary(
            $this->selectedMonth,
            $this->selectedYear,
            $this->selectedLevelId
        );
    }

    public function exportExcel(): void
    {
        $service = app(PayrollExportService::class);
        $this->redirect($service->exportToExcel(
            $this->selectedMonth,
            $this->selectedYear,
            $this->selectedLevelId
        )->getTargetUrl(), navigate: true);
    }
}