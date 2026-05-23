<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallmentPaymentResource\Pages;
use App\Models\InstallmentPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstallmentPaymentResource extends Resource
{
    protected static ?string $model = InstallmentPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('installment_id')
                    ->relationship('installment', 'code')
                    ->disabled(),
                Forms\Components\TextInput::make('installment_number')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->disabled()
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                Forms\Components\DatePicker::make('due_date')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label(__('admin/installment-payment-resource.fields.status'))
                    ->options([
                        'unpaid' => __('admin/installment-payment-resource.status_options.unpaid'),
                        'partial' => __('admin/installment-payment-resource.status_options.partial'),
                        'paid' => __('admin/installment-payment-resource.status_options.paid'),
                        'overdue' => __('admin/installment-payment-resource.status_options.overdue'),
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('paid_amount')
                    ->disabled(),
                Forms\Components\DatePicker::make('paid_date')
                    ->disabled(),
                Forms\Components\Select::make('payment_method')
                    ->label(__('admin/installment-payment-resource.fields.payment_method'))
                    ->options([
                        'payroll_deduction' => __('admin/installment-payment-resource.payment_method_options.payroll_deduction'),
                        'manual' => __('admin/installment-payment-resource.payment_method_options.manual'),
                        'transfer' => __('admin/installment-payment-resource.payment_method_options.transfer'),
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label(__('admin/installment-payment-resource.fields.notes')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('installment.uuid')
                    ->label(__('admin/installment-payment-resource.columns.installment_code'))
                    ->getStateUsing(fn (InstallmentPayment $record): ?string => $record->installment?->code)
                    ->searchable(),
                Tables\Columns\TextColumn::make('installment.customer.full_name')
                    ->label(__('admin/installment-payment-resource.columns.member'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('installment_number')
                    ->label(__('admin/installment-payment-resource.columns.installment_number'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('admin/installment-payment-resource.columns.amount'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('admin/installment-payment-resource.columns.due_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('admin/installment-payment-resource.columns.status'))
                    ->colors([
                        'warning' => 'unpaid',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('admin/installment-payment-resource.columns.paid_amount'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->label(__('admin/installment-payment-resource.columns.paid_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label(__('admin/installment-payment-resource.columns.payment_method'))
                    ->colors([
                        'info' => 'payroll_deduction',
                        'success' => 'manual',
                        'primary' => 'transfer',
                    ]),
                Tables\Columns\BadgeColumn::make('payroll_status')
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
                SelectFilter::make('status')
                    ->label(__('admin/installment-payment-resource.filters.status'))
                    ->options([
                        'unpaid' => __('admin/installment-payment-resource.status_options.unpaid'),
                        'partial' => __('admin/installment-payment-resource.status_options.partial'),
                        'paid' => __('admin/installment-payment-resource.status_options.paid'),
                        'overdue' => __('admin/installment-payment-resource.status_options.overdue'),
                    ]),
                SelectFilter::make('payment_method')
                    ->label(__('admin/installment-payment-resource.filters.payment_method'))
                    ->options([
                        'payroll_deduction' => __('admin/installment-payment-resource.payment_method_options.payroll_deduction'),
                        'manual' => __('admin/installment-payment-resource.payment_method_options.manual'),
                        'transfer' => __('admin/installment-payment-resource.payment_method_options.transfer'),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallmentPayments::route('/'),
            'view' => Pages\ViewInstallmentPayment::route('/{record}'),
            'edit' => Pages\EditInstallmentPayment::route('/{record}/edit'),
        ];
    }
}
