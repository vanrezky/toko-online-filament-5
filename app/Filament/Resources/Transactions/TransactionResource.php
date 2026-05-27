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
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Resources\Transactions\Pages\EditTransaction;
use App\Services\NavigationBadgeCache;
use App\Enums\CourierCode;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

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

                        $allPickup = $details->every(fn ($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value);
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

                        if ($details->isNotEmpty() && $details->every(fn ($detail) => strtoupper((string) $detail->courier_code) === CourierCode::PICKUP->value)) {
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
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
