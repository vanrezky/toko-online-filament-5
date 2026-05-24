<?php

namespace App\Filament\Resources\Balances;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Balances\Pages\ListBalances;
use App\Filament\Resources\Balances\Pages\CreateBalance;
use App\Filament\Resources\Balances\Pages\EditBalance;
use App\Models\Balance;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;


class BalanceResource extends Resource
{
    protected static ?string $model = Balance::class;


    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Customer';
    protected static ?string $slug = 'balances';
    protected static ?int $navigationSort = 3;

    // @feature-toggle: balance — set to true & remove canAccess() to re-enable
    static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false; // @feature-toggle: balance — change to parent::canAccess() to re-enable
    }

    protected static array $trxTypeOptions = [
        '+' => 'Deposit',
        '-' => 'Reduce',
    ];
    protected static array $trxTypeColor = [
        'Deposit' => 'success',
        'Reduce' => 'danger',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer')
                            ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->full_name)
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->optionsLimit(20)
                            ->required(),
                        Select::make('trx_type')
                            ->options(fn(): array => self::$trxTypeOptions)
                            ->required()
                            ->searchable(),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->default(0.00),
                        TextInput::make('notes')
                            ->required()
                            ->minLength(3)
                            ->maxLength(255),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.full_name')
                    ->numeric()
                    ->sortable(false),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('charge')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('post_balance')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('trx_type')
                    ->getStateUsing(fn(Balance $record): string => self::getTrxTypeLabel($record->trx_type))
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => self::getTrxTypeColor($state)),
                TextColumn::make('notes')
                    ->searchable(),
                TextColumn::make('remark')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('created_at', direction: 'DESC')
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => ListBalances::route('/'),
            'create' => CreateBalance::route('/create'),
            'edit' => EditBalance::route('/{record}/edit'),
        ];
    }

    public static function getTrxTypeColor(string $trxType): string
    {
        return self::$trxTypeColor[$trxType] ?? 'gray';
    }

    public static function getTrxTypeLabel(string $trxType): string
    {
        return self::$trxTypeOptions[$trxType] ?? 'Unknown';
    }
}
