<?php

namespace App\Filament\Resources\CustomerLevels;

use App\Filament\Clusters\CustomerCluster;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CustomerLevels\Pages\ListCustomerLevels;
use App\Filament\Resources\CustomerLevels\Pages\CreateCustomerLevel;
use App\Filament\Resources\CustomerLevels\Pages\ViewCustomerLevel;
use App\Filament\Resources\CustomerLevels\Pages\EditCustomerLevel;
use App\Models\CustomerLevel;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerLevelResource extends Resource
{
    protected static ?string $model = CustomerLevel::class;

    protected static ?string $cluster = CustomerCluster::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $slug = 'customer-levels';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('admin/customer-level-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/customer-level-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/customer-level-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/customer-level-resource.sections.level_data'))
                    ->description(__('admin/customer-level-resource.sections.level_data_description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin/customer-level-resource.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                        TextInput::make('slug')
                            ->label(__('admin/customer-level-resource.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('admin/customer-level-resource.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('admin/customer-level-resource.sections.credit_settings'))
                    ->description(__('admin/customer-level-resource.sections.credit_settings_description'))
                    ->hidden()
                    ->schema([
                        TextInput::make('default_credit_limit')
                            ->label(__('admin/customer-level-resource.fields.default_credit_limit'))
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        Toggle::make('is_active')
                            ->label(__('admin/customer-level-resource.fields.is_active'))
                            ->default(true)
                            ->disabled(fn(?CustomerLevel $record) => $record?->hasCustomers())
                            ->helperText(fn(?CustomerLevel $record) => $record?->hasCustomers() ? __('admin/customer-level-resource.fields.is_active_helper') : null),
                    ])->columns(2),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/customer-level-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('default_credit_limit')
                    ->label(__('admin/customer-level-resource.columns.default_credit_limit'))
                    ->money('IDR')
                    ->sortable()
                    ->hidden(),
                IconColumn::make('is_active')
                    ->label(__('admin/customer-level-resource.columns.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('customers_count')
                    ->label(__('admin/customer-level-resource.columns.customers_count'))
                    ->counts('customers')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/customer-level-resource.filters.is_active'))
                    ->placeholder(__('admin/customer-level-resource.filters.is_active_placeholder'))
                    ->trueLabel(__('admin/customer-level-resource.filters.is_active_true'))
                    ->falseLabel(__('admin/customer-level-resource.filters.is_active_false')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn(CustomerLevel $record) => $record->hasCustomers())
                    ->tooltip(fn(CustomerLevel $record) => $record->hasCustomers() ? __('admin/customer-level-resource.actions.delete_tooltip') : __('admin/customer-level-resource.actions.delete_allowed_tooltip')),
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
            'index' => ListCustomerLevels::route('/'),
            'create' => CreateCustomerLevel::route('/create'),
            'view' => ViewCustomerLevel::route('/{record}'),
            'edit' => EditCustomerLevel::route('/{record}/edit'),
        ];
    }
}
