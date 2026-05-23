<?php

namespace App\Filament\Resources\Villages;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Villages\Pages\ListVillages;
use App\Models\Village;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VillageResource extends Resource
{
    protected static ?string $model = Village::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Wilayah';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sub_district_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(100),
                // Forms\Components\TextInput::make('postal_code')
                //     ->maxLength(10),
                // Forms\Components\TextInput::make('rajaongkir')
                //     ->maxLength(255),
                // Forms\Components\TextInput::make('apicoid_code')
                //     ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subDistrict.name'),
                TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('postal_code')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('rajaongkir')
                //     ->label('Rajaongkir Code')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('apicoid_code')
                //     ->label('Apicoid Code')
                //     ->searchable(),
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
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListVillages::route('/'),
            // 'create' => Pages\CreateVillage::route('/create'),
            // 'edit' => Pages\EditVillage::route('/{record}/edit'),
        ];
    }
}
