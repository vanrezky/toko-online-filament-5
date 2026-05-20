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
    protected static ?string $navigationGroup = 'Master';
    protected static ?string $slug = 'installment-payments';
    protected static ?int $navigationSort = 4;

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
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ])
                    ->disabled(),
                Forms\Components\TextInput::make('paid_amount')
                    ->disabled(),
                Forms\Components\DatePicker::make('paid_date')
                    ->disabled(),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'payroll_deduction' => 'Potong Gaji',
                        'manual' => 'Bayar Manual',
                        'transfer' => 'Transfer',
                    ]),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('installment.uuid')
                    ->label('Kode Cicilan')
                    ->getStateUsing(fn (InstallmentPayment $record): ?string => $record->installment?->code)
                    ->searchable(),
                Tables\Columns\TextColumn::make('installment.customer.full_name')
                    ->label('Anggota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Angsuran Ke')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
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
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->colors([
                        'info' => 'payroll_deduction',
                        'success' => 'manual',
                        'primary' => 'transfer',
                    ]),
                Tables\Columns\BadgeColumn::make('payroll_status')
                    ->label('Status Payroll')
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
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Terlambat',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'payroll_deduction' => 'Potong Gaji',
                        'manual' => 'Bayar Manual',
                        'transfer' => 'Transfer',
                    ]),
                SelectFilter::make('payroll_status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'batched' => 'Batched',
                        'submitted' => 'Submitted',
                        'confirmed_paid' => 'Confirmed Paid',
                        'failed' => 'Failed',
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
                Section::make('Informasi Pembayaran Cicilan')
                    ->schema([
                        TextEntry::make('installment.uuid')
                            ->label('Kode Cicilan')
                            ->getStateUsing(fn (InstallmentPayment $record): ?string => $record->installment?->code),
                        TextEntry::make('installment.customer.full_name')
                            ->label('Anggota'),
                        TextEntry::make('installment_number')
                            ->label('Angsuran Ke'),
                        TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR'),
                        TextEntry::make('due_date')
                            ->label('Jatuh Tempo')
                            ->date(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'partial' => 'info',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'unpaid' => 'Belum Bayar',
                                'partial' => 'Sebagian',
                                'paid' => 'Lunas',
                                'overdue' => 'Terlambat',
                                default => $state,
                            }),
                        TextEntry::make('paid_amount')
                            ->label('Dibayar')
                            ->money('IDR'),
                        TextEntry::make('paid_date')
                            ->label('Tgl Bayar')
                            ->date(),
                        TextEntry::make('payment_method')
                            ->label('Metode')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'payroll_deduction' => 'info',
                                'manual' => 'success',
                                'transfer' => 'primary',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'payroll_deduction' => 'Potong Gaji',
                                'manual' => 'Bayar Manual',
                                'transfer' => 'Transfer',
                                default => '-',
                            }),
                        TextEntry::make('notes')
                            ->label('Catatan')
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
