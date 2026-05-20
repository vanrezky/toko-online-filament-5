<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallmentPlanResource\Pages;
use App\Models\InstallmentPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentPlanResource extends Resource
{
    protected static ?string $model = InstallmentPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin/installment-plan-resource.sections.tenor'))
                    ->description(__('admin/installment-plan-resource.sections.tenor_description'))
                    ->schema([
                        Forms\Components\TextInput::make('tenor')
                            ->label(__('admin/installment-plan-resource.fields.tenor'))
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('fee_percentage')
                            ->label(__('admin/installment-plan-resource.fields.fee_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('admin/installment-plan-resource.fields.description'))
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin/installment-plan-resource.sections.status'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('admin/installment-plan-resource.fields.is_active'))
                            ->default(true)
                            ->disabled(fn(?InstallmentPlan $record) => $record?->installments()->exists())
                            ->helperText(fn(?InstallmentPlan $record) => $record?->installments()->exists() ? __('admin/installment-plan-resource.fields.is_active_helper') : null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenor')
                    ->label(__('admin/installment-plan-resource.columns.tenor'))
                    ->formatStateUsing(fn(int $state) => $state . ' ' . __('admin/installment-plan-resource.columns.tenor_format'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fee_percentage')
                    ->label(__('admin/installment-plan-resource.columns.fee_percentage'))
                    ->formatStateUsing(fn(float $state) => $state . __('admin/installment-plan-resource.columns.fee_format'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('admin/installment-plan-resource.columns.description'))
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin/installment-plan-resource.columns.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installments_count')
                    ->label(__('admin/installment-plan-resource.columns.installments_count'))
                    ->counts('installments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin/installment-plan-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('admin/installment-plan-resource.filters.is_active'))
                    ->placeholder(__('admin/installment-plan-resource.filters.is_active_placeholder'))
                    ->trueLabel(__('admin/installment-plan-resource.filters.is_active_true'))
                    ->falseLabel(__('admin/installment-plan-resource.filters.is_active_false')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(InstallmentPlan $record) => $record->installments()->exists())
                    ->tooltip(fn(InstallmentPlan $record) => $record->installments()->exists() ? __('admin/installment-plan-resource.actions.delete_tooltip') : __('admin/installment-plan-resource.actions.delete_allowed_tooltip')),
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
            'index' => Pages\ListInstallmentPlans::route('/'),
            'create' => Pages\CreateInstallmentPlan::route('/create'),
            'view' => Pages\ViewInstallmentPlan::route('/{record}'),
            'edit' => Pages\EditInstallmentPlan::route('/{record}/edit'),
        ];
    }
}