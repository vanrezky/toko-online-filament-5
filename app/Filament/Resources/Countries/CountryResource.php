<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Clusters\RegionCluster;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\RelationManagers\ProvincesRelationManager;
use App\Models\Country;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $cluster = RegionCluster::class;
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin/country-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/country-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/country-resource.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/country-resource.fields.country_information'))
                    ->schema([
                        TextInput::make('iso')
                            ->label(__('admin/country-resource.fields.iso'))
                            ->required()
                            ->maxLength(2),
                        TextInput::make('name')
                            ->label(__('admin/country-resource.fields.name'))
                            ->required()
                            ->maxLength(255),
                    ])->columns(2)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('iso')
                    ->label(__('admin/country-resource.columns.iso'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('admin/country-resource.columns.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin/country-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin/country-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make()
                //     ->successNotification(
                //         Notification::make()
                //             ->success()
                //             ->title('Country deleted')
                //             ->body('The country has been deleted successfully.'),
                //     )
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()
                //         ->successNotification(Notification::make()
                //             ->success()
                //             ->title('Country deleted')
                //             ->body('The country has been deleted successfully.')),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProvincesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            // 'create' => Pages\CreateCountry::route('/create'),
            // 'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
