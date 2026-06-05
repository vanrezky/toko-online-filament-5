<?php

namespace App\Filament\Resources\InstallmentPayments;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\InstallmentPayments\Pages\ListInstallmentPayments;
use App\Filament\Resources\InstallmentPayments\Pages\ViewInstallmentPayment;
use App\Filament\Resources\InstallmentPayments\Pages\EditInstallmentPayment;
use App\Models\Customer;
use App\Models\InstallmentPayment;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstallmentPaymentResource extends Resource
{
    protected static ?string $model = InstallmentPayment::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $slug = 'installment-payments';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin/installment-payment-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/installment-payment-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/installment-payment-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/installment-payment-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('installment_id')
                    ->relationship('installment', 'code')
                    ->disabled(),
                TextInput::make('installment_number')
                    ->disabled(),
                TextInput::make('amount')
                    ->disabled(),
                DatePicker::make('due_date')
                    ->disabled(),
                Select::make('status')
                    ->label(__('admin/installment-payment-resource.fields.status'))
                    ->options([
                        'unpaid' => __('admin/installment-payment-resource.status_options.unpaid'),
                        'partial' => __('admin/installment-payment-resource.status_options.partial'),
                        'paid' => __('admin/installment-payment-resource.status_options.paid'),
                        'overdue' => __('admin/installment-payment-resource.status_options.overdue'),
                    ])
                    ->disabled(),
                TextInput::make('paid_amount')
                    ->disabled(),
                DatePicker::make('paid_date')
                    ->disabled(),
                Select::make('payment_method')
                    ->label(__('admin/installment-payment-resource.fields.payment_method'))
                    ->options([
                        'payroll_deduction' => __('admin/installment-payment-resource.payment_method_options.payroll_deduction'),
                        'manual' => __('admin/installment-payment-resource.payment_method_options.manual'),
                        'transfer' => __('admin/installment-payment-resource.payment_method_options.transfer'),
                    ]),
                Textarea::make('notes')
                    ->label(__('admin/installment-payment-resource.fields.notes')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('installment.uuid')
                    ->label(__('admin/installment-payment-resource.columns.installment_code'))
                    ->getStateUsing(fn (InstallmentPayment $record): ?string => $record->installment?->code)
                    ->searchable(),
                TextColumn::make('installment.customer.full_name')
                    ->label(__('admin/installment-payment-resource.columns.member'))
                    ->searchable(),
                TextColumn::make('installment_number')
                    ->label(__('admin/installment-payment-resource.columns.installment_number'))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('admin/installment-payment-resource.columns.amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('admin/installment-payment-resource.columns.due_date'))
                    ->date()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label(__('admin/installment-payment-resource.columns.status'))
                    ->colors([
                        'warning' => 'unpaid',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
                TextColumn::make('paid_amount')
                    ->label(__('admin/installment-payment-resource.columns.paid_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_date')
                    ->label(__('admin/installment-payment-resource.columns.paid_date'))
                    ->date()
                    ->sortable(),
                BadgeColumn::make('payment_method')
                    ->label(__('admin/installment-payment-resource.columns.payment_method'))
                    ->colors([
                        'info' => 'payroll_deduction',
                        'success' => 'manual',
                        'primary' => 'transfer',
                    ]),
                BadgeColumn::make('payroll_status')
                    ->label(__('admin/installment-payment-resource.columns.payroll_status'))
                    ->colors([
                        'warning' => 'scheduled',
                        'info' => 'batched',
                        'primary' => 'submitted',
                        'success' => 'confirmed_paid',
                        'danger' => 'failed',
                    ]),
            ])
            ->filters([
                Filter::make('customer_transaction')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('customer_id')
                            ->label(__('admin/installment-payment-resource.filters.customer'))
                            ->options(fn (): array => Customer::query()
                                ->whereHas('installments.payments')
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    $customer->id => $customer->full_name,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpan(1)
                            ->afterStateUpdated(fn (callable $set) => $set('transaction_id', null)),
                        Select::make('transaction_id')
                            ->label(__('admin/installment-payment-resource.filters.transaction'))
                            ->options(fn (Get $get): array => Transaction::query()
                                ->where('customer_id', $get('customer_id'))
                                ->whereHas('installment.payments')
                                ->orderByDesc('created_at')
                                ->get()
                                ->mapWithKeys(fn (Transaction $transaction): array => [
                                    $transaction->id => $transaction->code,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->disabled(fn (Get $get): bool => blank($get('customer_id'))),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['customer_id'] ?? null,
                                fn (Builder $query, $customerId): Builder => $query->whereHas(
                                    'installment',
                                    fn (Builder $installmentQuery): Builder => $installmentQuery->where('customer_id', $customerId)
                                )
                            )
                            ->when(
                                $data['transaction_id'] ?? null,
                                fn (Builder $query, $transactionId): Builder => $query->whereHas(
                                    'installment',
                                    fn (Builder $installmentQuery): Builder => $installmentQuery->where('transaction_id', $transactionId)
                                )
                            );
                    }),
                SelectFilter::make('status')
                    ->label(__('admin/installment-payment-resource.filters.status'))
                    ->options([
                        'unpaid' => __('admin/installment-payment-resource.status_options.unpaid'),
                        'partial' => __('admin/installment-payment-resource.status_options.partial'),
                        'paid' => __('admin/installment-payment-resource.status_options.paid'),
                        'overdue' => __('admin/installment-payment-resource.status_options.overdue'),
                    ]),
                SelectFilter::make('payroll_status')
                    ->label(__('admin/installment-payment-resource.filters.payroll_status'))
                    ->options([
                        'scheduled' => __('admin/installment-payment-resource.payroll_status_options.scheduled'),
                        'batched' => __('admin/installment-payment-resource.payroll_status_options.batched'),
                        'submitted' => __('admin/installment-payment-resource.payroll_status_options.submitted'),
                        'confirmed_paid' => __('admin/installment-payment-resource.payroll_status_options.confirmed_paid'),
                        'failed' => __('admin/installment-payment-resource.payroll_status_options.failed'),
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/installment-payment-resource.sections.payment_info'))
                    ->schema([
                        TextEntry::make('installment.uuid')
                            ->label(__('admin/installment-payment-resource.fields.installment_code'))
                            ->getStateUsing(fn (InstallmentPayment $record): ?string => $record->installment?->code),
                        TextEntry::make('installment.customer.full_name')
                            ->label(__('admin/installment-payment-resource.fields.member')),
                        TextEntry::make('installment_number')
                            ->label(__('admin/installment-payment-resource.fields.installment_number')),
                        TextEntry::make('amount')
                            ->label(__('admin/installment-payment-resource.fields.amount'))
                            ->money('IDR'),
                        TextEntry::make('due_date')
                            ->label(__('admin/installment-payment-resource.fields.due_date'))
                            ->date(),
                        TextEntry::make('status')
                            ->label(__('admin/installment-payment-resource.fields.status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'partial' => 'info',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => __('admin/installment-payment-resource.status_options.' . $state)),
                        TextEntry::make('paid_amount')
                            ->label(__('admin/installment-payment-resource.fields.paid_amount'))
                            ->money('IDR'),
                        TextEntry::make('paid_date')
                            ->label(__('admin/installment-payment-resource.fields.paid_date'))
                            ->date(),
                        TextEntry::make('payment_method')
                            ->label(__('admin/installment-payment-resource.fields.payment_method'))
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'payroll_deduction' => 'info',
                                'manual' => 'success',
                                'transfer' => 'primary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => $state
                                ? __('admin/installment-payment-resource.payment_method_options.' . $state)
                                : '-'),
                        TextEntry::make('notes')
                            ->label(__('admin/installment-payment-resource.fields.notes'))
                            ->placeholder('-'),
                    ])->columns(2),
            ])
            ->columns(1);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallmentPayments::route('/'),
            'view' => ViewInstallmentPayment::route('/{record}'),
            'edit' => EditInstallmentPayment::route('/{record}/edit'),
        ];
    }
}
