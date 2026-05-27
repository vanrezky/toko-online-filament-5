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
    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin/province-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/province-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/province-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/province-resource.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/province-resource.fields.province_information'))
                    ->schema([
                        Select::make('country_id')
                            ->label(__('admin/province-resource.fields.country_id'))
                            ->relationship('country', titleAttribute: 'name')
                            ->placeholder(__('admin/province-resource.fields.country_placeholder'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin/province-resource.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('rajaongkir')
                            ->label(__('admin/province-resource.fields.rajaongkir'))
                            ->required()
                            ->maxLength(255),
                    ])->columns(2)
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/province-resource.fields.province_information'))
                    ->schema([
                        TextEntry::make('country.name')->label(__('admin/province-resource.columns.country_name')),
                        TextEntry::make('name')->label(__('admin/province-resource.columns.name')),
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
                    ->label(__('admin/province-resource.columns.country_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin/province-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('rajaongkir')
                //     ->searchable()
                //     ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin/province-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin/province-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label(__('admin/province-resource.fields.country_id'))
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
