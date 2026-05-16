<?php

namespace App\Filament\Resources\InstallmentResource\RelationManagers;

use App\Models\InstallmentPayment;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Jadwal Pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Ke')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
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
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Tgl Bayar')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode'),
            ])
            ->actions([
                Action::make('mark_payroll')
                    ->label('Potong Gaji')
                    ->action(function (InstallmentPayment $record) {
                        $record->update(['payment_method' => 'payroll_deduction']);
                    })
                    ->visible(fn(InstallmentPayment $record) => $record->status !== 'paid'),
                Action::make('mark_paid')
                    ->label('Bayar Manual')
                    ->form([
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Nominal')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan'),
                    ])
                    ->action(function (InstallmentPayment $record, array $data) {
                        $record->markAsPaid($data['paid_amount'], 'manual', $data['notes'] ?? null);
                    })
                    ->visible(fn(InstallmentPayment $record) => $record->status !== 'paid'),
            ]);
    }
}