<?php

namespace App\Filament\Resources\Warehouses;

use App\Services\NavigationBadgeCache;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Override;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home-modern';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin/warehouse-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/warehouse-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/warehouse-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/warehouse-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = NavigationBadgeCache::getWarehouseCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/warehouse-resource.fields.location'))
                    ->schema([
                        Select::make('province_id')
                            ->label(__('admin/warehouse-resource.fields.province_id'))
                            ->relationship('province', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('district_id', null))
                            ->required(),
                        Select::make('district_id')
                            ->label(__('admin/warehouse-resource.fields.district_id'))
                            ->relationship('district', 'name', fn($query, $get) => $query->where('province_id', $get('province_id')))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('sub_district_id', null))
                            ->required(),
                        Select::make('sub_district_id')
                            ->label(__('admin/warehouse-resource.fields.sub_district_id'))
                            ->relationship('subDistrict', 'name', fn($query, $get) => $query->where('district_id', $get('district_id')))
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('village_id', null))
                            ->required(),
                        Select::make('village_id')
                            ->label(__('admin/warehouse-resource.fields.village_id'))
                            ->relationship('village', 'name', fn($query, $get) => $query->where('sub_district_id', $get('sub_district_id')))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('postal_code')
                            ->label(__('admin/warehouse-resource.fields.postal_code'))
                            ->maxLength(10),
                    ])->columns(2),
                Section::make(__('admin/warehouse-resource.fields.warehouse_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/warehouse-resource.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label(__('admin/warehouse-resource.fields.address'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('contact_name')
                            ->label(__('admin/warehouse-resource.fields.contact_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label(__('admin/warehouse-resource.fields.contact_phone'))
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('courier')
                            ->label(__('admin/warehouse-resource.fields.courier'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('description')
                            ->label(__('admin/warehouse-resource.fields.description'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('admin/warehouse-resource.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subDistrict.name')
                    ->label(__('admin/warehouse-resource.fields.sub_district_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin/warehouse-resource.columns.name'))
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label(__('admin/warehouse-resource.columns.contact_name'))
                    ->searchable(),
                TextColumn::make('contact_phone')
                    ->label(__('admin/warehouse-resource.columns.contact_phone'))
                    ->searchable(),
                TextColumn::make('courier')
                    ->label(__('admin/warehouse-resource.columns.courier'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                self::getIsActiveColumn(),
                TextColumn::make('description')
                    ->label(__('admin/warehouse-resource.columns.description'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('admin/warehouse-resource.notifications.activation_updated'), 'success'))
                ->label(__('admin/warehouse-resource.fields.is_active'));
        }

        return IconColumn::make('is_active')->boolean()->label(__('admin/warehouse-resource.columns.is_active'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_warehouse');
    }
}
