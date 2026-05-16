<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallmentResource\Pages;
use App\Filament\Resources\InstallmentResource\RelationManagers\PaymentsRelationManager;
use App\Models\Installment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $slug = 'installments';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Cicilan')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->disabled(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'first_name')
                            ->disabled(),
                        Forms\Components\Select::make('installment_plan_id')
                            ->relationship('installmentPlan', 'tenor')
                            ->disabled(),
                        Forms\Components\TextInput::make('principal_amount')
                            ->label('Harga Pokok')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\TextInput::make('fee_amount')
                            ->label('Fee')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Cicilan')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\TextInput::make('monthly_amount')
                            ->label('Angsuran/Bulan')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                    ])->columns(2),
                Forms\Components\Section::make('Progress Pembayaran')
                    ->schema([
                        Forms\Components\TextInput::make('tenor')
                            ->label('Tenor')
                            ->disabled(),
                        Forms\Components\TextInput::make('paid_installments')
                            ->label('Sudah Dibayar')
                            ->disabled(),
                        Forms\Components\TextInput::make('paid_amount')
                            ->label('Total Dibayar')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('Sisa')
                            ->disabled()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'completed' => 'Lunas',
                                'overdue' => 'Terlambat',
                                'defaulted' => 'Wanprestasi',
                            ])
                            ->disabled(),
                        Forms\Components\TextInput::make('start_date')
                            ->disabled(),
                        Forms\Components\TextInput::make('expected_end_date')
                            ->label('Tgl Selesai')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('Kode')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Anggota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.customerLevel.name')
                    ->label('Level')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Cicilan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_amount')
                    ->label('Angsuran/Bulan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenor')
                    ->label('Tenor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_installments')
                    ->label('Dibayar')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'active',
                        'success' => 'completed',
                        'danger' => 'overdue',
                        'gray' => 'defaulted',
                    ]),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'completed' => 'Lunas',
                        'overdue' => 'Terlambat',
                        'defaulted' => 'Wanprestasi',
                    ]),
                SelectFilter::make('customer_level')
                    ->relationship('customer.customerLevel', 'name')
                    ->label('Level Anggota'),
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

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallments::route('/'),
            'view' => Pages\ViewInstallment::route('/{record}'),
        ];
    }
}