<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\PromotionCluster;
use App\Filament\Resources\FlashsaleResource\Pages;
use App\Filament\Resources\FlashsaleResource\RelationManagers\ProductsRelationManager;
use App\Models\Flashsale;
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

    protected static ?string $navigationLabel = 'Flashsale';

    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Flashsale')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    DateTimePicker::make('start_time')
                        ->required(),
                    DateTimePicker::make('end_time')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Legacy is_active')
                        ->helperText('Frontend visibility tetap mengikuti Template dengan code `flashsale`.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Legacy Active'),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
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
