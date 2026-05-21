<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Override;

class CustomerAddressRelationManager extends RelationManager
{
    protected static string $relationship = 'address';
    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin/customer-resource.address.title');
    }



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('admin/customer-resource.address.fields.name'))
                    ->placeholder(__('admin/customer-resource.address.placeholders.name'))
                    ->default(fn() => $this->getOwnerRecord()?->schoolUnit?->name ?? $this->getOwnerRecord()?->full_name)
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(255),
                Forms\Components\Select::make('province_id')
                    ->label(__('admin/customer-resource.address.fields.province_id'))
                    ->options(Province::where('country_id', 1)->get()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('district', null);
                        $set('sub_district_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('district_id')
                    ->label(__('admin/customer-resource.address.fields.district_id'))
                    ->options(function (Get $get, string $operation): Collection {
                        return District::where('province_id', $get('province_id'))->get()->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('sub_district_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('sub_district_id')
                    ->label(__('admin/customer-resource.address.fields.sub_district_id'))
                    ->options(function (Get $get, string $operation, ?string $state): Collection {
                        return SubDistrict::where('district_id', $get('district_id'))->get()->pluck('name', 'id');
                    })
                    ->searchable()->preload()
                    ->required(),
                Forms\Components\Textarea::make('address')
                    ->label(__('admin/customer-resource.address.fields.address'))
                    ->maxLength(300)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('phone')
                    ->label(__('admin/customer-resource.address.fields.phone'))
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('postal_code')
                    ->label(__('admin/customer-resource.address.fields.postal_code'))
                    ->numeric()
                    ->required()
                    ->minLength(5)
                    ->maxLength(5)

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin/customer-resource.address.columns.name')),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin/customer-resource.address.columns.phone')),
                Tables\Columns\TextColumn::make('province.name')
                    ->label(__('admin/customer-resource.address.columns.province')),
                Tables\Columns\TextColumn::make('district.name')
                    ->label(__('admin/customer-resource.address.columns.district')),
                Tables\Columns\TextColumn::make('subDistrict.name')
                    ->label(__('admin/customer-resource.address.columns.sub_district')),
                Tables\Columns\TextColumn::make('postal_code')
                    ->label(__('admin/customer-resource.address.columns.postal_code')),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
