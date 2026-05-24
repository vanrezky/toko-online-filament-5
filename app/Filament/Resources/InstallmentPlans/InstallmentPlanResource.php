<?php

namespace App\Filament\Resources\InstallmentPlans;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
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
use App\Filament\Resources\InstallmentPlans\Pages\ListInstallmentPlans;
use App\Filament\Resources\InstallmentPlans\Pages\CreateInstallmentPlan;
use App\Filament\Resources\InstallmentPlans\Pages\ViewInstallmentPlan;
use App\Filament\Resources\InstallmentPlans\Pages\EditInstallmentPlan;
use App\Models\InstallmentPlan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentPlanResource extends Resource
{
    protected static ?string $model = InstallmentPlan::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'installment-plans';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'tenor';

    public static function getNavigationLabel(): string
    {
        return __('admin/installment-plan-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/installment-plan-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/installment-plan-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/installment-plan-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/installment-plan-resource.sections.tenor'))
                    ->description(__('admin/installment-plan-resource.sections.tenor_description'))
                    ->schema([
                        TextInput::make('tenor')
                            ->label(__('admin/installment-plan-resource.fields.tenor'))
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('fee_percentage')
                            ->label(__('admin/installment-plan-resource.fields.fee_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                        Textarea::make('description')
                            ->label(__('admin/installment-plan-resource.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('admin/installment-plan-resource.sections.status'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin/installment-plan-resource.fields.is_active'))
                            ->default(true)
                            ->disabled(fn(?InstallmentPlan $record) => $record?->installments()->exists())
                            ->helperText(fn(?InstallmentPlan $record) => $record?->installments()->exists() ? __('admin/installment-plan-resource.fields.is_active_helper') : null),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenor')
                    ->label(__('admin/installment-plan-resource.columns.tenor'))
                    ->formatStateUsing(fn(int $state) => $state . ' ' . __('admin/installment-plan-resource.columns.tenor_format'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fee_percentage')
                    ->label(__('admin/installment-plan-resource.columns.fee_percentage'))
                    ->formatStateUsing(fn(float $state) => $state . __('admin/installment-plan-resource.columns.fee_format'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('admin/installment-plan-resource.columns.description'))
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('admin/installment-plan-resource.columns.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('installments_count')
                    ->label(__('admin/installment-plan-resource.columns.installments_count'))
                    ->counts('installments')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin/installment-plan-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin/installment-plan-resource.filters.is_active'))
                    ->placeholder(__('admin/installment-plan-resource.filters.is_active_placeholder'))
                    ->trueLabel(__('admin/installment-plan-resource.filters.is_active_true'))
                    ->falseLabel(__('admin/installment-plan-resource.filters.is_active_false')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn(InstallmentPlan $record) => $record->installments()->exists())
                    ->tooltip(fn(InstallmentPlan $record) => $record->installments()->exists() ? __('admin/installment-plan-resource.actions.delete_tooltip') : __('admin/installment-plan-resource.actions.delete_allowed_tooltip')),
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
            'index' => ListInstallmentPlans::route('/'),
            'create' => CreateInstallmentPlan::route('/create'),
            'view' => ViewInstallmentPlan::route('/{record}'),
            'edit' => EditInstallmentPlan::route('/{record}/edit'),
        ];
    }
}
