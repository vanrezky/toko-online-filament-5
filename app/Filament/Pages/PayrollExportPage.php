<?php

namespace App\Filament\Pages;

use App\Services\PayrollExportService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;

class PayrollExportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Export Payroll';
    protected static ?string $title = 'Export Potongan Gaji';
    protected static ?string $slug = 'payroll-export';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.payroll-export';

    public int $selectedMonth;
    public int $selectedYear;
    public ?int $selectedLevelId = null;
    public array $previewData = [];

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('selectedMonth')
                ->label('Bulan')
                ->options([
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember',
                ])
                ->required(),
            Forms\Components\Select::make('selectedYear')
                ->label('Tahun')
                ->options(array_combine(range(now()->year - 5, now()->year + 1), range(now()->year - 5, now()->year + 1)))
                ->required(),
            Forms\Components\Select::make('selectedLevelId')
                ->label('Level Anggota')
                ->relationship('customerLevel', 'name')
                ->nullable()
                ->searchable(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->action('previewData'),
            Action::make('exportExcel')
                ->label('Export Excel')
                ->action('exportExcel')
                ->color('success'),
        ];
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