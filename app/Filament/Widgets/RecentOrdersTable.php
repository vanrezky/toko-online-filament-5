<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersTable extends BaseWidget
{
    protected int $cacheSeconds = 300;

    protected int $pageSize = 5;

    protected static ?string $heading = 'Recent Orders';

    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->with('customer')
            ->select(['transactions.*'])
            ->selectSub(function ($query) {
                $query->selectRaw('SUM((price * quantity) - discount)')
                    ->from('transcation_products')
                    ->whereColumn('transaction_id', 'transactions.id');
            }, 'total_amount')
            ->orderByDesc('created_at')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('uuid')
                ->label('Order ID')
                ->formatStateUsing(fn (string $state): string => strtoupper(substr($state, 0, 8)))
                ->url(fn (Transaction $record): string => route('filament.admin.resources.transactions.view', $record->uuid))
                ->searchable(),

            TextColumn::make('customer.full_name')
                ->label('Customer')
                ->searchable(),

            TextColumn::make('total_amount')
                ->label('Amount')
                ->money('IDR')
                ->sortable(),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'unpaid' => 'warning',
                    'packed', 'in_transit', 'shipped' => 'info',
                    'delivered', 'picked_up', 'completed' => 'success',
                    'rejected', 'cancelled' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('d M Y, H:i')
                ->sortable(),
        ];
    }
}
