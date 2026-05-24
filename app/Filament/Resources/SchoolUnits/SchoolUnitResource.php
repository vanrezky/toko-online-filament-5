<?php

namespace App\Filament\Resources\SchoolUnits;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SchoolUnits\Pages\ListSchoolUnits;
use App\Filament\Resources\SchoolUnits\Pages\CreateSchoolUnit;
use App\Filament\Resources\SchoolUnits\Pages\EditSchoolUnit;
use App\Models\SchoolUnit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolUnitResource extends Resource
{
    protected static ?string $model = SchoolUnit::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin/school-unit-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/school-unit-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/school-unit-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/school-unit-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->label(__('admin/school-unit-resource.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(__('admin/school-unit-resource.fields.phone'))
                        ->tel()
                        ->maxLength(255),
                    Select::make('province_id')
                        ->label(__('admin/school-unit-resource.fields.province_id'))
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('district_id', null))
                        ->required(),
                    Select::make('district_id')
                        ->label(__('admin/school-unit-resource.fields.district_id'))
                        ->relationship('district', 'name', fn($query, $get) => $query->where('province_id', $get('province_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('sub_district_id', null))
                        ->required(),
                    Select::make('sub_district_id')
                        ->label(__('admin/school-unit-resource.fields.sub_district_id'))
                        ->relationship('subDistrict', 'name', fn($query, $get) => $query->where('district_id', $get('district_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('village_id', null))
                        ->required(),
                    Select::make('village_id')
                        ->label(__('admin/school-unit-resource.fields.village_id'))
                        ->relationship('village', 'name', fn($query, $get) => $query->where('sub_district_id', $get('sub_district_id')))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('address')
                        ->label(__('admin/school-unit-resource.fields.address'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('postal_code')
                        ->label(__('admin/school-unit-resource.fields.postal_code'))
                        ->required()
                        ->maxLength(20),
                ])->columns(2)
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin/school-unit-resource.columns.name'))->searchable(),
                TextColumn::make('phone')->label(__('admin/school-unit-resource.columns.phone'))->searchable(),
                TextColumn::make('district.name')->label(__('admin/school-unit-resource.columns.district'))->searchable(),
                TextColumn::make('subDistrict.name')->label(__('admin/school-unit-resource.columns.sub_district'))->searchable(),
                TextColumn::make('postal_code')->label(__('admin/school-unit-resource.columns.postal_code'))->searchable(),
                TextColumn::make('customers_count')->counts('customers')->label(__('admin/school-unit-resource.columns.customers_count')),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolUnits::route('/'),
            'create' => CreateSchoolUnit::route('/create'),
            'edit' => EditSchoolUnit::route('/{record}/edit'),
        ];
    }
}
