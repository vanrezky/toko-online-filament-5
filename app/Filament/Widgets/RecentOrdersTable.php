<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int $cacheSeconds = 300;

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5]);
    }

    protected function getTableHeading(): string
    {
        return __('admin/page-dashboard.recent_orders.title');
    }

    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->with('customer')
            ->when(
                $this->pageFilters['startDate'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $this->pageFilters['endDate'] ?? null,
                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
            )
            ->when(
                $this->pageFilters['transactionStatus'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('transactions.status', $status),
            )
            ->select(['transactions.*'])
            ->selectSub(function ($query) {
                $query->selectRaw('SUM((price * quantity) - discount)')
                    ->from('transcation_products')
                    ->whereColumn('transaction_id', 'transactions.id');
            }, 'total_amount')
            ->orderByDesc('created_at');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('uuid')
                ->label(__('admin/page-dashboard.recent_orders.columns.order_id'))
                ->formatStateUsing(fn (string $state): string => strtoupper(substr($state, 0, 8)))
                ->url(fn (Transaction $record): string => route('filament.admin.resources.transactions.view', $record->uuid))
                ->searchable(),

            TextColumn::make('customer.full_name')
                ->label(__('admin/page-dashboard.recent_orders.columns.customer'))
                ->searchable(),

            TextColumn::make('total_amount')
                ->label(__('admin/page-dashboard.recent_orders.columns.amount'))
                ->money('IDR')
                ->sortable(),

            TextColumn::make('status')
                ->label(__('admin/page-dashboard.recent_orders.columns.status'))
                ->badge()
                ->formatStateUsing(function (TransactionStatus|string|null $state): string {
                    if ($state instanceof TransactionStatus) {
                        return (string) $state->getLabel();
                    }

                    return is_string($state) && $state !== ''
                        ? ucfirst($state)
                        : '-';
                }),

            TextColumn::make('created_at')
                ->label(__('admin/page-dashboard.recent_orders.columns.date'))
                ->dateTime('d M Y, H:i')
                ->sortable(),
        ];
    }
}
