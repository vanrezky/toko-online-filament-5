<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Transactions\Widgets\TransactionStatsOverview;
use App\Models\Transaction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()
                ->label('Semua')
                ->badge(Transaction::query()->count())
                ->badgeColor('primary'),
        ];

        foreach (TransactionStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make()
                ->label((string) $status->getLabel())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status->value))
                ->badge(Transaction::query()->where('status', $status->value)->count())
                ->badgeColor($status->getColor() ?? 'gray');
        }

        return $tabs;
    }
}
