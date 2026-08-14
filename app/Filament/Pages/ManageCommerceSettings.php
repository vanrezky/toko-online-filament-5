<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use App\Services\BillingDueDateSyncService;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageCommerceSettings extends SettingsPage
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'commerce';

    protected static string $settings = GeneralSettings::class;

    protected array $previousBillingSettings = [];

    protected ?array $billingDueDateSyncSummary = null;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-manage-commerce.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin/page-manage-commerce.title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset(
            $data['mail_from'],
            $data['mail_host'],
            $data['mail_port'],
            $data['mail_encryption'],
            $data['mail_username'],
            $data['mail_password'],
        );

        return $data;
    }

    protected function beforeSave(): void
    {
        $settings = app(GeneralSettings::class);

        $this->previousBillingSettings = [
            'billing_due_day' => $settings->billing_due_day,
            'billing_due_month_offset' => $settings->billing_due_month_offset,
        ];
    }

    protected function afterSave(): void
    {
        $settings = app(GeneralSettings::class);

        $this->billingDueDateSyncSummary = app(BillingDueDateSyncService::class)->syncForSettingsChange(
            $this->previousBillingSettings,
            [
                'billing_due_day' => $settings->billing_due_day,
                'billing_due_month_offset' => $settings->billing_due_month_offset,
            ],
        );
    }

    protected function getSavedNotificationMessage(): ?string
    {
        if (! $this->billingDueDateSyncSummary) {
            return __('admin/page-manage-commerce.notifications.saved');
        }

        $transactionsUpdated = (int) ($this->billingDueDateSyncSummary['transactions_updated'] ?? 0);
        $installmentPaymentsUpdated = (int) ($this->billingDueDateSyncSummary['installment_payments_updated'] ?? 0);

        if (($transactionsUpdated + $installmentPaymentsUpdated) === 0) {
            return __('admin/page-manage-commerce.notifications.saved');
        }

        return __('admin/page-manage-commerce.notifications.billing_schedule_synced', [
            'transactions' => $transactionsUpdated,
            'installments' => $installmentPaymentsUpdated,
        ]);
    }

    public function form(Schema $schema): Schema
    {
            return $schema
            ->components([
            Section::make(__('admin/page-manage-commerce.sections.customer_balance'))
                ->description(__('admin/page-manage-commerce.descriptions.customer_balance'))
                ->columnSpanFull()
                ->schema([
                        Toggle::make('balance_enabled')
                            ->label(__('admin/page-manage-commerce.fields.balance_enabled'))
                            ->helperText(__('admin/page-manage-commerce.fields.balance_enabled_helper')),
                    ]),
            Section::make(__('admin/page-manage-commerce.sections.credit_checkout'))
                ->description(__('admin/page-manage-commerce.descriptions.credit_checkout'))
                ->columnSpanFull()
                ->schema([
                        Toggle::make('enforce_credit_limit')
                            ->label(__('admin/page-manage-commerce.fields.enforce_credit_limit'))
                            ->helperText(__('admin/page-manage-commerce.fields.enforce_credit_limit_helper'))
                            ->default(true)
                            ->required(),
                        TextInput::make('installment_min_order_amount')
                            ->label(__('admin/page-manage-commerce.fields.installment_min_order_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->default(1000000)
                            ->required(),
                        TextInput::make('transaction_time_limit_minutes')
                            ->label(__('admin/page-manage-commerce.fields.transaction_time_limit_minutes'))
                            ->helperText(__('admin/page-manage-commerce.fields.transaction_time_limit_minutes_helper'))
                            ->numeric()->minValue(5)->maxValue(10080)->default(1440)->required(),
                    ])
                    ->columns(2),
            Section::make(__('admin/page-manage-commerce.sections.billing_cycle'))
                ->description(__('admin/page-manage-commerce.descriptions.billing_cycle'))
                ->columnSpanFull()
                ->schema([
                        TextInput::make('billing_cutoff_day')
                            ->label(__('admin/page-manage-commerce.fields.billing_cutoff_day'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->default(25)
                            ->required(),
                        TextInput::make('billing_due_day')
                            ->label(__('admin/page-manage-commerce.fields.billing_due_day'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->default(5)
                            ->required(),
                        TextInput::make('billing_due_month_offset')
                            ->label(__('admin/page-manage-commerce.fields.billing_due_month_offset'))
                            ->helperText(__('admin/page-manage-commerce.fields.billing_due_month_offset_helper'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(12)
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}
