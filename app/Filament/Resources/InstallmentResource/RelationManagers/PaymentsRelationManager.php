<?php

namespace App\Filament\Resources\InstallmentResource\RelationManagers;

use App\Models\InstallmentPayment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
                Tables\Columns\TextColumn::make('installment_number')
                    ->label(__('admin/installment-resource.payments.columns.installment_number'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('admin/installment-resource.payments.columns.amount'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('admin/installment-resource.payments.columns.due_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'unpaid',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ]),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label(__('admin/installment-resource.payments.columns.paid_amount'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->label(__('admin/installment-resource.payments.columns.paid_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('admin/installment-resource.payments.columns.payment_method')),
            ])
            ->actions([
                Action::make('mark_payroll')
                    ->label(__('admin/installment-resource.payments.actions.mark_payroll'))
                    ->action(function (InstallmentPayment $record) {
                        $record->update(['payment_method' => 'payroll_deduction']);
                    })
                    ->visible(fn(InstallmentPayment $record) => $record->status !== 'paid'),
                Action::make('mark_paid')
                    ->label(__('admin/installment-resource.payments.actions.mark_paid'))
                    ->form([
                        Forms\Components\TextInput::make('paid_amount')
                            ->label(__('admin/installment-resource.payments.actions.paid_amount'))
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
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