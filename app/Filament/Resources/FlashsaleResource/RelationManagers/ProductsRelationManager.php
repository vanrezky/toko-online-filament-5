<?php

namespace App\Filament\Resources\FlashsaleResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->relationship(
                    name: 'product',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        $owner = $this->getOwnerRecord();
                        $existingIds = $owner->products()->pluck('product_id');

                        return $query->when(
                            filled($this->getMountedTableActionRecord()),
                            fn (Builder $builder) => $builder,
                            fn (Builder $builder) => $builder->whereNotIn('id', $existingIds)
                        );
                    }
                )
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('discount_percentage')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->required(),
            TextInput::make('stock')
                ->numeric()
                ->minValue(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('product.category.name')
                    ->label('Category'),
                TextColumn::make('discount_percentage')
                    ->suffix('%'),
                TextColumn::make('stock'),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }
}
