<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Models\Balance;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class BalancesRelationManager extends RelationManager
{
    protected static string $relationship = 'balances';

    protected static array $trxTypeOptions = [
        '+' => 'Deposit',
        '-' => 'Reduce',
    ];
    protected static array $trxTypeColor = [
        'Deposit' => 'success',
        'Reduce' => 'danger',
    ];

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Date'))->dateTime()
                    ->sortable(),
                TextColumn::make('amount')->money('IDR')->sortable(),
                TextColumn::make('charge')->money('IDR')->sortable(),
                TextColumn::make('post_balance')->money('IDR'),
                TextColumn::make('trx_type')
                    ->getStateUsing(fn(Balance $record): string => self::getTrxTypeLabel($record->trx_type))
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => self::getTrxTypeColor($state)),
                TextColumn::make('notes')->searchable(),
                TextColumn::make('remark')->searchable(),
            ])

            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[On('refreshBalanceRelationManager')]
    public function refresh(): void {}

    public static function getTrxTypeColor(string $trxType): string
    {
        return self::$trxTypeColor[$trxType] ?? 'gray';
    }

    public static function getTrxTypeLabel(string $trxType): string
    {
        return self::$trxTypeOptions[$trxType] ?? 'Unknown';
    }
}
