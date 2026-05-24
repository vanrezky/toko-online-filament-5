<?php

namespace App\Filament\Resources\Provinces;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Provinces\Pages\ListProvinces;
use App\Models\Province;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Provinsi';
    protected static string | \UnitEnum | null $navigationGroup = 'Wilayah';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Province Information')
                    ->schema([
                        Select::make('country_id')
                            // ->label('Country')
                            ->relationship('country', titleAttribute: 'name')
                            // ->options(Country::all()->pluck('name', 'id'))
                            ->placeholder('Select Country')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rajaongkir')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2)
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Province Information')
                    ->schema([
                        TextEntry::make('country.name')->label('Country name'),
                        TextEntry::make('name')->label('Pronvice name'),
                        // TextEntry::make('rajaongkir')->label('Rajaongkir code'),
                    ])->columns(2)
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('rajaongkir')
                //     ->searchable()
                //     ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('Country')
                    ->relationship('country', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
            ])
            ->recordActions([
                ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListProvinces::route('/'),
            // 'create' => Pages\CreateProvince::route('/create'),
            // 'edit' => Pages\EditProvince::route('/{record}/edit'),
        ];
    }
}
