<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use App\Models\CustomerLevel;
use App\Services\PayrollExportService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PayrollExportPage extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';
    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.payroll-export';

    public ?int $selectedMonth = null;
    public ?int $selectedYear = null;
    public ?int $selectedLevelId = null;
    public array $previewData = [];

    protected function canExport(): bool
    {
        if ($this->previewData === []) {
            return false;
        }

        return (int) ($this->previewData['total_customers'] ?? 0) > 0;
    }

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
                ->action('generatePreview'),
            Action::make('exportDraft')
                ->label(__('admin/payroll-export-page.actions.export_draft_excel'))
                ->action('exportDraft')
                ->color('success')
                ->disabled(fn (): bool => ! $this->canExport())
                ->extraAttributes(fn (): array => ! $this->canExport() ? ['style' => 'cursor: not-allowed;'] : []),
            Action::make('exportFinal')
                ->label(__('admin/payroll-export-page.actions.export_final_submit'))
                ->color('danger')
                ->disabled(fn (): bool => ! $this->canExport())
                ->extraAttributes(fn (): array => ! $this->canExport() ? ['style' => 'cursor: not-allowed;'] : [])
                ->requiresConfirmation()
                ->modalSubmitActionLabel(__('admin/payroll-export-page.actions.export_final_submit'))
                ->modalHeading(__('admin/payroll-export-page.confirmations.final_heading'))
                ->modalDescription(function (): string {
                    $summary = app(PayrollExportService::class)->getPayrollSummary(
                        $this->selectedMonth,
                        $this->selectedYear,
                        $this->selectedLevelId
                    );

                    return __('admin/payroll-export-page.confirmations.final_description', [
                        'month' => $summary['month_name'] ?? '-',
                        'year' => $summary['year'] ?? '-',
                        'customers' => $summary['total_customers'] ?? 0,
                        'full_bills' => $summary['total_full_bills'] ?? 0,
                        'installments' => $summary['total_installments'] ?? 0,
                        'total' => number_format((float) ($summary['total_deduction'] ?? 0), 0, ',', '.'),
                    ]);
                })
                ->action(fn () => $this->exportFinalAndSubmit()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)
                ->schema([
                    Select::make('selectedMonth')
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
                    Select::make('selectedYear')
                        ->label(__('admin/payroll-export-page.fields.year'))
                        ->options(array_combine(range(now()->year - 5, now()->year + 1), range(now()->year - 5, now()->year + 1)))
                        ->columnSpan(1)
                        ->required(),
                    Select::make('selectedLevelId')
                        ->label(__('admin/payroll-export-page.fields.customer_level'))
                        ->options(CustomerLevel::query()->pluck('name', 'id'))
                        ->columnSpan(1)
                        ->nullable()
                        ->searchable(),
                ]),
        ]);
    }

    public function generatePreview(): void
    {
        $service = app(PayrollExportService::class);
        $summary = $service->getPayrollSummary(
            $this->selectedMonth,
            $this->selectedYear,
            $this->selectedLevelId
        );

        $summary['details'] = collect($summary['details'] ?? [])->map(function (array $detail) {
            $payments = collect($detail['payments'] ?? [])->map(function ($item) {
                return [
                    'type' => (string) ($item['type'] ?? '-'),
                    'reference' => (string) ($item['reference'] ?? '-'),
                    'amount' => (float) ($item['amount'] ?? 0),
                    'transaction_uuid' => $item['transaction_uuid'] ?? null,
                ];
            })->values();

            $fullPayments = $payments->where('type', 'full');
            $installmentPayments = $payments->where('type', 'installment');

            return [
                'customer_id' => $detail['customer']->id,
                'customer_name' => $detail['customer']->full_name,
                'level' => $detail['level'] ?? 'N/A',
                'total_deduction' => (float) ($detail['total_deduction'] ?? 0),
                'active_installments' => (int) ($detail['active_installments'] ?? 0),
                'references' => $detail['references'] ?? '-',
                'full_bill_count' => $fullPayments->count(),
                'full_bill_total' => (float) $fullPayments->sum('amount'),
                'installment_bill_count' => $installmentPayments->count(),
                'installment_bill_total' => (float) $installmentPayments->sum('amount'),
                'payments' => $payments->all(),
            ];
        })->values()->all();

        $this->previewData = $summary;
    }

    public function exportDraft()
    {
        if (! $this->canExport()) {
            Notification::make()
                ->title(__('admin/payroll-export-page.notifications.no_eligible_items_title'))
                ->body(__('admin/payroll-export-page.notifications.no_eligible_items_body'))
                ->warning()
                ->send();

            return null;
        }

        $service = app(PayrollExportService::class);
        return $service->exportToExcel(
            $this->selectedMonth,
            $this->selectedYear,
            $this->selectedLevelId
        );
    }

    public function exportFinalAndSubmit()
    {
        if (! $this->canExport()) {
            Notification::make()
                ->title(__('admin/payroll-export-page.notifications.no_eligible_items_title'))
                ->body(__('admin/payroll-export-page.notifications.no_eligible_items_body'))
                ->warning()
                ->send();

            return null;
        }

        $service = app(PayrollExportService::class);
        $result = $service->submitAndExportFinal(
            $this->selectedMonth,
            $this->selectedYear,
            $this->selectedLevelId
        );

        if (($result['total_updated'] ?? 0) === 0) {
            Notification::make()
                ->title(__('admin/payroll-export-page.notifications.no_eligible_items_title'))
                ->body(__('admin/payroll-export-page.notifications.no_eligible_items_body'))
                ->warning()
                ->send();

            return null;
        }

        Notification::make()
            ->title(__('admin/payroll-export-page.notifications.final_export_success_title'))
            ->body(__('admin/payroll-export-page.notifications.final_export_success_body', [
                'batch' => $result['batch_reference'],
                'total' => $result['total_updated'],
            ]))
            ->success()
            ->send();

        return $result['response'];
    }
}
