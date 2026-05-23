<?php

namespace App\Filament\Resources\Resellers;

use App\Services\NavigationBadgeCache;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Resellers\Pages\ListResellers;
use App\Filament\Resources\Resellers\Pages\CreateReseller;
use App\Filament\Resources\Resellers\Pages\EditReseller;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\Reseller;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResellerResource extends Resource
{
    protected static ?string $model = Reseller::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Reseller Level';
    protected static string | \UnitEnum | null $navigationGroup = 'Customer';
    protected static ?string $slug = 'reseller-level';
    protected static ?int $navigationSort = 5;

    // @feature-toggle: reseller — set to true & remove canAccess() to re-enable
    static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false; // @feature-toggle: reseller — change to parent::canAccess() to re-enable
    }

    public static function getNavigationBadge(): ?string
    {
        $count = NavigationBadgeCache::getResellerCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->placeholder(__('e.g: Agent'))
                            ->maxLength(50)
                            ->columnSpan(2),
                        TextInput::make('description')
                            ->placeholder(__('e.g: Agent is a user who can sell products'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(3),
                        TextInput::make('level')
                            ->placeholder(__('e.g: 5'))
                            ->required()
                            ->maxValue(10)
                            ->numeric()
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label('Is Level Active')
                            ->required()
                            ->columnSpanFull()
                            ->default(true),
                    ])->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('level')
                    ->searchable(),
                self::getIsActiveColumn(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->action(function ($record) {
                    if ($record->customers()->count()) {
                        return notification(__('Reseller Level cannot be deleted'), 'warning');
                    }

                    $record->delete();
                    return notification(__('Reseller Level cannot be deleted'), 'success');
                }),
            ])
            ->toolbarActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResellers::route('/'),
            'create' => CreateReseller::route('/create'),
            'edit' => EditReseller::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('Activation status updated successfully'), 'success'))
                ->label(__('Active'));
        }

        return IconColumn::make('is_active')->boolean()->label(__('Active'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_reseller');
    }
}
