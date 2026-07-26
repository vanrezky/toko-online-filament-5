<?php

namespace App\Filament\Resources\Flashsales;

use App\Filament\Clusters\PromotionCluster;
use App\Filament\Resources\Flashsales\Pages;
use App\Filament\Resources\Flashsales\RelationManagers\ProductsRelationManager;
use App\Models\Flashsale;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FlashsaleResource extends Resource
{
    protected static ?string $model = Flashsale::class;

    protected static ?string $cluster = PromotionCluster::class;

    protected static ?string $slug = 'flashsales';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?int $navigationSort = 35;

    public static function getNavigationLabel(): string
    {
        return __('admin/flashsale-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/flashsale-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/flashsale-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/flashsale-resource.sections.information'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin/flashsale-resource.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label(__('admin/flashsale-resource.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpan(2),
                Section::make(__('admin/flashsale-resource.sections.schedule'))
                    ->schema([
                    DateTimePicker::make('start_time')
                        ->label(__('admin/flashsale-resource.fields.start_time'))
                        ->required(),
                    DateTimePicker::make('end_time')
                        ->label(__('admin/flashsale-resource.fields.end_time'))
                        ->required()
                        ->after('start_time'),
                    Toggle::make('is_active')
                        ->label(__('admin/flashsale-resource.fields.is_active'))
                        ->helperText(__('admin/flashsale-resource.fields.is_active_helper')),
                ])
                ->columns(1)
                ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/flashsale-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('admin/flashsale-resource.columns.is_active')),
                TextColumn::make('start_time')
                    ->label(__('admin/flashsale-resource.columns.start_time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label(__('admin/flashsale-resource.columns.end_time'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label(__('admin/flashsale-resource.columns.products')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlashsales::route('/'),
            'create' => Pages\CreateFlashsale::route('/create'),
            'edit' => Pages\EditFlashsale::route('/{record}/edit'),
        ];
    }
}
