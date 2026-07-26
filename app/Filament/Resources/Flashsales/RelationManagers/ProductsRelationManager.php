<?php

namespace App\Filament\Resources\Flashsales\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                ->label(__('admin/flashsale-resource.fields.product'))
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
                ->required()
                ->columnSpanFull(),
            TextInput::make('discount_percentage')
                ->label(__('admin/flashsale-resource.fields.discount_percentage'))
                ->numeric()
                ->minValue(25)
                ->maxValue(100)
                ->helperText(__('admin/flashsale-resource.fields.discount_percentage_helper'))
                ->required(),
            TextInput::make('stock')
                ->label(__('admin/flashsale-resource.fields.stock'))
                ->numeric()
                ->minValue(0)
                ->required(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('product.name')
                    ->label(__('admin/flashsale-resource.columns.product'))
                    ->searchable(),
                TextColumn::make('product.category.name')
                    ->label(__('admin/flashsale-resource.columns.category')),
                TextColumn::make('discount_percentage')
                    ->label(__('admin/flashsale-resource.columns.discount_percentage'))
                    ->suffix('%'),
                TextColumn::make('stock')
                    ->label(__('admin/flashsale-resource.columns.stock')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
