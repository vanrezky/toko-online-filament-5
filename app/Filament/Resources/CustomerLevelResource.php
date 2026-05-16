<?php

namespace App\Filament\Resources;

use App\Models\CustomerLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerLevelResource extends Resource
{
    protected static ?string $model = CustomerLevel::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Toko Private';
    protected static ?string $slug = 'customer-levels';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('default_credit_limit')
                    ->label('Default Credit Limit')
                    ->numeric()
                    ->prefix('Rp')
                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_credit_limit')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('customers_count')
                    ->label('Customers')
                    ->counts('customers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->form([
                        Forms\Components\Toggle::make('active'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(isset($data['active']), function ($q) use ($data) {
                            $q->where('is_active', $data['active']);
                        });
                    }),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CustomerLevelResource\Pages\ListCustomerLevels::route('/'),
            'create' => \App\Filament\Resources\CustomerLevelResource\Pages\CreateCustomerLevel::route('/create'),
            'view' => \App\Filament\Resources\CustomerLevelResource\Pages\ViewCustomerLevel::route('/{record}'),
            'edit' => \App\Filament\Resources\CustomerLevelResource\Pages\EditCustomerLevel::route('/{record}/edit'),
        ];
    }
}