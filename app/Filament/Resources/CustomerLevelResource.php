<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerLevelResource\Pages;
use App\Models\CustomerLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerLevelResource extends Resource
{
    protected static ?string $model = CustomerLevel::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $slug = 'customer-levels';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('admin/customer-level-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/customer-level-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/customer-level-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/customer-level-resource.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin/customer-level-resource.sections.level_data'))
                    ->description(__('admin/customer-level-resource.sections.level_data_description'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin/customer-level-resource.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin/customer-level-resource.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('admin/customer-level-resource.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin/customer-level-resource.sections.credit_settings'))
                    ->description(__('admin/customer-level-resource.sections.credit_settings_description'))
                    ->schema([
                        Forms\Components\TextInput::make('default_credit_limit')
                            ->label(__('admin/customer-level-resource.fields.default_credit_limit'))
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin/customer-level-resource.fields.is_active'))
                            ->default(true)
                            ->disabled(fn(?CustomerLevel $record) => $record?->hasCustomers())
                            ->helperText(fn(?CustomerLevel $record) => $record?->hasCustomers() ? __('admin/customer-level-resource.fields.is_active_helper') : null),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin/customer-level-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('default_credit_limit')
                    ->label(__('admin/customer-level-resource.columns.default_credit_limit'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin/customer-level-resource.columns.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customers_count')
                    ->label(__('admin/customer-level-resource.columns.customers_count'))
                    ->counts('customers')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin/customer-level-resource.filters.is_active'))
                    ->placeholder(__('admin/customer-level-resource.filters.is_active_placeholder'))
                    ->trueLabel(__('admin/customer-level-resource.filters.is_active_true'))
                    ->falseLabel(__('admin/customer-level-resource.filters.is_active_false')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(CustomerLevel $record) => $record->hasCustomers())
                    ->tooltip(fn(CustomerLevel $record) => $record->hasCustomers() ? __('admin/customer-level-resource.actions.delete_tooltip') : __('admin/customer-level-resource.actions.delete_allowed_tooltip')),
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
            'index' => Pages\ListCustomerLevels::route('/'),
            'create' => Pages\CreateCustomerLevel::route('/create'),
            'view' => Pages\ViewCustomerLevel::route('/{record}'),
            'edit' => Pages\EditCustomerLevel::route('/{record}/edit'),
        ];
    }
}