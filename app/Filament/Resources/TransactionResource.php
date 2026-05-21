<?php

namespace App\Filament\Resources;

use App\Enums\CourierCode;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin/transaction-resource.fields.order_information'))
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label(__('admin/transaction-resource.fields.order_id'))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label(__('admin/transaction-resource.fields.status'))
                            ->options([
                                'unpaid' => __('admin/transaction-resource.status.unpaid'),
                                'packed' => __('admin/transaction-resource.status.packed'),
                                'in_transit' => __('admin/transaction-resource.status.in_transit'),
                                'shipped' => __('admin/transaction-resource.status.shipped'),
                                'delivered' => __('admin/transaction-resource.status.delivered'),
                                'picked_up' => __('admin/transaction-resource.status.picked_up'),
                                'rejected' => __('admin/transaction-resource.status.rejected'),
                                'cancelled' => __('admin/transaction-resource.status.cancelled'),
                                'completed' => __('admin/transaction-resource.status.completed'),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('receipt_code')
                            ->label(__('admin/transaction-resource.fields.receipt_code'))
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('timelimit')
                            ->label(__('admin/transaction-resource.fields.timelimit')),
                        Forms\Components\DateTimePicker::make('delivery_date')
                            ->label(__('admin/transaction-resource.fields.delivery_date')),
                        Forms\Components\DateTimePicker::make('complete_date')
                            ->label(__('admin/transaction-resource.fields.complete_date')),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin/transaction-resource.fields.customer_notes'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('admin/transaction-resource.fields.notes'))
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label(__('admin/transaction-resource.columns.order'))
                    ->getStateUsing(fn (Transaction $record): string => $record->code ?? '-')
                    ->searchable()
                    ->description(fn (Transaction $record) => $record->created_at->format('d M Y, H:i'))
                    ->tooltip(fn (Transaction $record) => $record->uuid),

                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label(__('admin/transaction-resource.columns.customer'))
                    ->description(fn (Transaction $record) => $record->customer?->email)
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('admin/transaction-resource.columns.total'))
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_items')
                    ->label(__('admin/transaction-resource.columns.items'))
                    ->suffix(' items'),

                Tables\Columns\TextColumn::make('shipping_method')
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

                Tables\Columns\TextColumn::make('shipping_address')
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

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'unpaid',
                        'info' => ['packed', 'in_transit', 'shipped'],
                        'success' => ['delivered', 'picked_up', 'completed'],
                        'danger' => ['rejected', 'cancelled'],
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(__("admin/transaction-resource.status.{$state}")))
                    ->sortable(),

                // Tables\Columns\IconColumn::make('cod')
                //     ->boolean()
                //     ->label(__('admin/transaction-resource.columns.cod'))
                //     ->trueIcon('heroicon-m-check')
                //     ->falseIcon('heroicon-m-x-mark'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
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
                    ->options([
                        'unpaid' => __('admin/transaction-resource.status.unpaid'),
                        'packed' => __('admin/transaction-resource.status.packed'),
                        'in_transit' => __('admin/transaction-resource.status.in_transit'),
                        'shipped' => __('admin/transaction-resource.status.shipped'),
                        'delivered' => __('admin/transaction-resource.status.delivered'),
                        'picked_up' => __('admin/transaction-resource.status.picked_up'),
                        'rejected' => __('admin/transaction-resource.status.rejected'),
                        'cancelled' => __('admin/transaction-resource.status.cancelled'),
                        'completed' => __('admin/transaction-resource.status.completed'),
                    ])
                    ->multiple()
                    ->preload(),

                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
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
                    ->form([
                        Forms\Components\Toggle::make('cod_only'),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) \App\Services\NavigationBadgeCache::getTransactionUnpaidCount();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
