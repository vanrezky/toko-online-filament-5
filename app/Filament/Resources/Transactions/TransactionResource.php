<?php

namespace App\Filament\Resources\Transactions;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Services\NavigationBadgeCache;
use App\Enums\CourierCode;
use App\Enums\TransactionStatus;
use App\Enums\TransactionBillingStatus;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Override;
use Filament\Notifications\Notification;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin/transaction-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/transaction-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/transaction-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/transaction-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/transaction-resource.fields.order_information'))
                    ->schema([
                        TextInput::make('uuid')
                            ->label(__('admin/transaction-resource.fields.order_id'))
                            ->disabled(),
                        Select::make('status')
                            ->label(__('admin/transaction-resource.fields.status'))
                            ->options(TransactionStatus::class)
                            ->required(),
                        TextInput::make('receipt_code')
                            ->label(__('admin/transaction-resource.fields.receipt_code'))
                            ->maxLength(255),
                        DateTimePicker::make('timelimit')
                            ->label(__('admin/transaction-resource.fields.timelimit')),
                        DateTimePicker::make('delivery_date')
                            ->label(__('admin/transaction-resource.fields.delivery_date')),
                        DateTimePicker::make('complete_date')
                            ->label(__('admin/transaction-resource.fields.complete_date')),
                    ])->columns(2),

                Section::make(__('admin/transaction-resource.fields.customer_notes'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('admin/transaction-resource.fields.notes'))
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label(__('admin/transaction-resource.columns.order'))
                    ->getStateUsing(fn (Transaction $record): string => $record->code ?? '-')
                    ->searchable()
                    ->description(fn (Transaction $record) => $record->created_at->format('d M Y, H:i'))
                    ->tooltip(fn (Transaction $record) => $record->uuid),

                TextColumn::make('customer.full_name')
                    ->label(__('admin/transaction-resource.columns.customer'))
                    ->description(fn (Transaction $record) => $record->customer?->email)
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label(__('admin/transaction-resource.columns.total'))
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('total_items')
                    ->label(__('admin/transaction-resource.columns.items'))
                    ->suffix(' items'),

                TextColumn::make('shipping_method')
                    ->label(__('admin/transaction-resource.columns.shipping_method'))
                    ->getStateUsing(function (Transaction $record): string {
                        $details = $record->shippingDetails;

                        if ($details->isEmpty()) {
                            return '-';
                        }

                        $allPickup = $details->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value);
                        if ($allPickup) {
                            return __('admin/transaction-resource.columns.pickup_only');
                        }

                        return $details
                            ->pluck('courier_name')
                            ->filter()
                            ->unique()
                            ->implode(', ');
                    })
                    ->toggleable(),

                TextColumn::make('shipping_address')
                    ->label(__('admin/transaction-resource.columns.shipping_address'))
                    ->getStateUsing(function (Transaction $record): string {
                        $details = $record->shippingDetails;

                        if ($details->isNotEmpty() && $details->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value)) {
                            return __('admin/transaction-resource.columns.pickup_no_address');
                        }

                        $address = $record->address;
                        if (! $address) {
                            return '-';
                        }

                        return implode(', ', array_filter([
                            $address->address,
                            $address->village?->name,
                            $address->subDistrict?->name,
                            $address->district?->name,
                            $address->province?->name,
                            $address->postal_code,
                        ]));
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('billing_status')
                    ->label('Billing')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\IconColumn::make('cod')
                //     ->boolean()
                //     ->label(__('admin/transaction-resource.columns.cod'))
                //     ->trueIcon('heroicon-m-check')
                //     ->falseIcon('heroicon-m-x-mark'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'customer',
                'address.province',
                'address.district',
                'address.subDistrict',
                'address.village',
                'shippingDetails',
            ]))
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin/transaction-resource.fields.status'))
                    ->options(TransactionStatus::class)
                    ->multiple()
                    ->preload(),

                SelectFilter::make('billing_status')
                    ->label('Billing Status')
                    ->options(TransactionBillingStatus::class)
                    ->multiple()
                    ->preload(),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query): Builder => $query->whereDate(
                                    'created_at',
                                    '>=',
                                    $data['created_from']
                                )
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query): Builder => $query->whereDate(
                                    'created_at',
                                    '<=',
                                    $data['created_until']
                                )
                            );
                    })
                    ->columns(2),

                Filter::make('cod')
                    ->schema([
                        Toggle::make('cod_only'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['cod_only'],
                            fn (Builder $query): Builder => $query->where('cod', true)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['cod_only']) {
                            return null;
                        }

                        return __('admin/transaction-resource.filters.cod_only');
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('row_mark_in_transit')
                        ->label(__('admin/transaction-resource.actions.mark_as_shipped'))
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(function (Transaction $record): bool {
                            $record->loadMissing('shippingDetails');
                            $isPickup = $record->shippingDetails->isNotEmpty()
                                && $record->shippingDetails->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value);

                            return $record->status === TransactionStatus::packed && ! $isPickup;
                        })
                        ->action(function (Transaction $record): void {
                            $record->update([
                                'status' => TransactionStatus::in_transit->value,
                                'delivery_date' => now(),
                            ]);

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->success()
                            ->send();
                        }),

                    Action::make('row_mark_picked_up')
                        ->label(__('admin/transaction-resource.actions.mark_as_picked_up'))
                        ->icon('heroicon-o-hand-raised')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(function (Transaction $record): bool {
                            $record->loadMissing('shippingDetails');
                            $isPickup = $record->shippingDetails->isNotEmpty()
                                && $record->shippingDetails->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value);

                            return $record->status === TransactionStatus::packed && $isPickup;
                        })
                        ->action(function (Transaction $record): void {
                            $record->update([
                                'status' => TransactionStatus::picked_up->value,
                                'delivery_date' => now(),
                            ]);

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->success()
                            ->send();
                        }),

                    Action::make('row_mark_delivered')
                        ->label(__('admin/transaction-resource.actions.mark_as_delivered'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Transaction $record): bool => in_array($record->status, [TransactionStatus::in_transit, TransactionStatus::shipped], true))
                        ->action(function (Transaction $record): void {
                            $record->update(['status' => TransactionStatus::delivered->value]);

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->success()
                            ->send();
                        }),

                    Action::make('row_mark_completed')
                        ->label(__('admin/transaction-resource.actions.mark_as_completed'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Transaction $record): bool => in_array($record->status, [TransactionStatus::delivered, TransactionStatus::picked_up], true))
                        ->action(function (Transaction $record): void {
                            $record->update([
                                'status' => TransactionStatus::completed->value,
                                'complete_date' => now(),
                            ]);

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->success()
                            ->send();
                        }),

                    Action::make('row_billing_submitted')
                        ->label('Tandai Tagihan Diajukan')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Transaction $record): bool => $record->payment_type === 'full' && in_array($record->billing_status, [TransactionBillingStatus::pending, TransactionBillingStatus::failed], true))
                        ->action(function (Transaction $record): void {
                            $record->update(['billing_status' => TransactionBillingStatus::submitted->value]);

                            Notification::make()
                                ->title('Billing status updated')
                                ->success()
                            ->send();
                        }),

                    Action::make('row_billing_paid')
                        ->label('Tandai Tagihan Lunas')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Transaction $record): bool => $record->payment_type === 'full' && $record->billing_status === TransactionBillingStatus::submitted)
                        ->action(function (Transaction $record): void {
                            $record->update(['billing_status' => TransactionBillingStatus::paid->value]);

                            Notification::make()
                                ->title('Billing status updated')
                                ->success()
                            ->send();
                        }),

                    Action::make('row_billing_failed')
                        ->label('Tandai Tagihan Gagal')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Transaction $record): bool => $record->payment_type === 'full' && $record->billing_status === TransactionBillingStatus::submitted)
                        ->action(function (Transaction $record): void {
                            $record->update(['billing_status' => TransactionBillingStatus::failed->value]);

                            Notification::make()
                                ->title('Billing status updated')
                                ->success()
                            ->send();
                        }),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_in_transit')
                        ->label(__('admin/transaction-resource.actions.mark_as_shipped'))
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            $records->loadMissing('shippingDetails');

                            foreach ($records as $record) {
                                $isPickup = $record->shippingDetails->isNotEmpty()
                                    && $record->shippingDetails->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value);

                                if ($record->status !== TransactionStatus::packed || $isPickup) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update([
                                    'status' => TransactionStatus::in_transit->value,
                                    'delivery_date' => now(),
                                ]);
                                $updated++;
                            }

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('mark_picked_up')
                        ->label(__('admin/transaction-resource.actions.mark_as_picked_up'))
                        ->icon('heroicon-o-hand-raised')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            $records->loadMissing('shippingDetails');

                            foreach ($records as $record) {
                                $isPickup = $record->shippingDetails->isNotEmpty()
                                    && $record->shippingDetails->every(fn ($detail) => strtolower((string) $detail->courier_code) === CourierCode::PICKUP->value);

                                if ($record->status !== TransactionStatus::packed || ! $isPickup) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update([
                                    'status' => TransactionStatus::picked_up->value,
                                    'delivery_date' => now(),
                                ]);
                                $updated++;
                            }

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('mark_delivered')
                        ->label(__('admin/transaction-resource.actions.mark_as_delivered'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! in_array($record->status, [TransactionStatus::in_transit, TransactionStatus::shipped], true)) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update(['status' => TransactionStatus::delivered->value]);
                                $updated++;
                            }

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('mark_completed')
                        ->label(__('admin/transaction-resource.actions.mark_as_completed'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! in_array($record->status, [TransactionStatus::delivered, TransactionStatus::picked_up], true)) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update([
                                    'status' => TransactionStatus::completed->value,
                                    'complete_date' => now(),
                                ]);
                                $updated++;
                            }

                            Notification::make()
                                ->title(__('admin/transaction-resource.notifications.status_updated'))
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('billing_submitted')
                        ->label('Tandai Tagihan Diajukan')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (
                                    $record->payment_type !== 'full'
                                    || ! in_array($record->billing_status, [TransactionBillingStatus::pending, TransactionBillingStatus::failed], true)
                                ) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update(['billing_status' => TransactionBillingStatus::submitted->value]);
                                $updated++;
                            }

                            Notification::make()
                                ->title('Billing status updated')
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('billing_paid')
                        ->label('Tandai Tagihan Lunas')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->payment_type !== 'full' || $record->billing_status !== TransactionBillingStatus::submitted) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update(['billing_status' => TransactionBillingStatus::paid->value]);
                                $updated++;
                            }

                            Notification::make()
                                ->title('Billing status updated')
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('billing_failed')
                        ->label('Tandai Tagihan Gagal')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if ($record->payment_type !== 'full' || $record->billing_status !== TransactionBillingStatus::submitted) {
                                    $skipped++;
                                    continue;
                                }

                                $record->update(['billing_status' => TransactionBillingStatus::failed->value]);
                                $updated++;
                            }

                            Notification::make()
                                ->title('Billing status updated')
                                ->body("Updated: {$updated}, skipped: {$skipped}")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
            'edit' => EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) NavigationBadgeCache::getTransactionCountByStatus(TransactionStatus::packed);
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
