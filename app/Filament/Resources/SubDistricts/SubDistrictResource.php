<?php

namespace App\Filament\Resources\SubDistricts;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use App\Filament\Resources\SubDistricts\Pages\ListSubDistricts;
use App\Models\SubDistrict;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubDistrictResource extends Resource
{
    protected static ?string $model = SubDistrict::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin/sub-district-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/sub-district-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/sub-district-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/sub-district-resource.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/sub-district-resource.fields.sub_district_information'))
                    ->schema([
                        Select::make('district_id')
                            ->label(__('admin/sub-district-resource.fields.district_id'))
                            ->relationship('district', titleAttribute: 'name')
                            ->searchable()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin/sub-district-resource.fields.name'))
                            ->required()
                            ->maxLength(255),
                        // Forms\Components\TextInput::make('rajaongkir')
                        //     ->required()
                        //     ->maxLength(50),
                        // Forms\Components\TextInput::make('postal_code')
                        //     ->required()
                        //     ->maxLength(10),
                    ])->columns(2)
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/sub-district-resource.fields.sub_district_information'))
                    ->schema([
                        TextEntry::make('district.name')->label(__('admin/sub-district-resource.columns.district_name')),
                        TextEntry::make('name')->label(__('admin/sub-district-resource.columns.name')),
                        // TextEntry::make('postal_code'),
                        // TextEntry::make('rajaongkir')->label('Rajaongkir code'),
                    ])->columns(2)
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('district.name')
                    ->label(__('admin/sub-district-resource.columns.district_name'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin/sub-district-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('rajaongkir')
                // ->searchable()
                // ->sortable(),
                // Tables\Columns\TextColumn::make('postal_code')
                //     ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin/sub-district-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin/sub-district-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('district_id')
                    ->label(__('admin/sub-district-resource.fields.district_id'))
                    ->relationship('district', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
            ])
            ->recordActions([
                ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
            'index' => ListSubDistricts::route('/'),
            // 'create' => Pages\CreateSubDistrict::route('/create'),
            // 'edit' => Pages\EditSubDistrict::route('/{record}/edit'),
        ];
    }
}
