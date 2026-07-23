<?php

namespace App\Filament\Resources\Districts;

use App\Filament\Clusters\RegionCluster;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use App\Filament\Resources\Districts\Pages\ListDistricts;
use App\Models\District;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $cluster = RegionCluster::class;
    // protected static ?string $slug = 'district';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin/district-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/district-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/district-resource.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('province_id')
                    ->label(__('admin/district-resource.fields.province_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->label(__('admin/district-resource.fields.type'))
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->label(__('admin/district-resource.fields.name'))
                    ->required()
                    ->maxLength(255),
                // Forms\Components\TextInput::make('rajaongkir')
                //     ->required()
                //     ->maxLength(20),
                TextInput::make('postal_code')
                    ->label(__('admin/district-resource.fields.postal_code'))
                    ->maxLength(255),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/district-resource.fields.district_information'))
                    ->schema([
                        TextEntry::make('province.name')->label(__('admin/district-resource.columns.province_name')),
                        TextEntry::make('name')->label(__('admin/district-resource.columns.name')),
                        TextEntry::make('type')->label(__('admin/district-resource.columns.type')),
                        TextEntry::make('postal_code')->label(__('admin/district-resource.columns.postal_code')),
                        // TextEntry::make('rajaongkir')->label('Rajaongkir code'),
                    ])->columns(2)
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('province.name')
                    ->label(__('admin/district-resource.columns.province_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin/district-resource.columns.type'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin/district-resource.columns.name'))
                    ->searchable(),
                // Tables\Columns\TextColumn::make('rajaongkir')
                //     ->searchable(),
                TextColumn::make('postal_code')
                    ->label(__('admin/district-resource.columns.postal_code'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin/district-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin/district-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                ViewAction::make(),
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
            'index' => ListDistricts::route('/'),
            // 'create' => Pages\CreateDistrict::route('/create'),
            // 'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }
}
