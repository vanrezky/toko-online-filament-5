<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolUnitResource\Pages;
use App\Models\SchoolUnit;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolUnitResource extends Resource
{
    protected static ?string $model = SchoolUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Forms\Components\TextInput::make('name')
                        ->label(__('admin/school-unit-resource.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label(__('admin/school-unit-resource.fields.phone'))
                        ->tel()
                        ->maxLength(255),
                    Forms\Components\Select::make('province_id')
                        ->label(__('admin/school-unit-resource.fields.province_id'))
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('district_id', null))
                        ->required(),
                    Forms\Components\Select::make('district_id')
                        ->label(__('admin/school-unit-resource.fields.district_id'))
                        ->relationship('district', 'name', fn($query, $get) => $query->where('province_id', $get('province_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('sub_district_id', null))
                        ->required(),
                    Forms\Components\Select::make('sub_district_id')
                        ->label(__('admin/school-unit-resource.fields.sub_district_id'))
                        ->relationship('subDistrict', 'name', fn($query, $get) => $query->where('district_id', $get('district_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(callable $set) => $set('village_id', null))
                        ->required(),
                    Forms\Components\Select::make('village_id')
                        ->label(__('admin/school-unit-resource.fields.village_id'))
                        ->relationship('village', 'name', fn($query, $get) => $query->where('sub_district_id', $get('sub_district_id')))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Textarea::make('address')
                        ->label(__('admin/school-unit-resource.fields.address'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('postal_code')
                        ->label(__('admin/school-unit-resource.fields.postal_code'))
                        ->required()
                        ->maxLength(20),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('admin/school-unit-resource.columns.name'))->searchable(),
                Tables\Columns\TextColumn::make('phone')->label(__('admin/school-unit-resource.columns.phone'))->searchable(),
                Tables\Columns\TextColumn::make('district.name')->label(__('admin/school-unit-resource.columns.district'))->searchable(),
                Tables\Columns\TextColumn::make('subDistrict.name')->label(__('admin/school-unit-resource.columns.sub_district'))->searchable(),
                Tables\Columns\TextColumn::make('postal_code')->label(__('admin/school-unit-resource.columns.postal_code'))->searchable(),
                Tables\Columns\TextColumn::make('customers_count')->counts('customers')->label(__('admin/school-unit-resource.columns.customers_count')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListSchoolUnits::route('/'),
            'create' => Pages\CreateSchoolUnit::route('/create'),
            'edit' => Pages\EditSchoolUnit::route('/{record}/edit'),
        ];
    }
}
