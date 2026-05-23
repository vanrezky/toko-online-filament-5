<?php

namespace App\Filament\Resources\Installments\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\InstallmentPayment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin/installment-resource.relation_managers.payments_title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('installment_number')
                    ->label(__('admin/installment-resource.payments.columns.installment_number'))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('admin/installment-resource.payments.columns.amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('admin/installment-resource.payments.columns.due_date'))
                    ->date()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'unpaid',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
                TextColumn::make('paid_amount')
                    ->label(__('admin/installment-resource.payments.columns.paid_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('paid_date')
                    ->label(__('admin/installment-resource.payments.columns.paid_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('admin/installment-resource.payments.columns.payment_method')),
            ])
            ->recordActions([
                Action::make('mark_payroll')
                    ->label(__('admin/installment-resource.payments.actions.mark_payroll'))
                    ->action(function (InstallmentPayment $record) {
                        $record->update(['payment_method' => 'payroll_deduction']);
                    })
                    ->visible(fn(InstallmentPayment $record) => $record->status !== 'paid'),
                Action::make('mark_paid')
                    ->label(__('admin/installment-resource.payments.actions.mark_paid'))
                    ->schema([
                        TextInput::make('paid_amount')
                            ->label(__('admin/installment-resource.payments.actions.paid_amount'))
                            ->numeric()
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('admin/installment-resource.payments.actions.notes'))
                            ->columnSpanFull(),
                    ])
                    ->action(function (InstallmentPayment $record, array $data) {
                        $record->markAsPaid($data['paid_amount'], 'manual', $data['notes'] ?? null);
                    })
                    ->visible(fn(InstallmentPayment $record) => $record->status !== 'paid'),
            ]);
    }
}