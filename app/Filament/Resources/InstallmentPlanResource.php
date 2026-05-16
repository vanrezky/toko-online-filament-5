<?php

namespace App\Filament\Resources;

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
    protected static ?string $navigationGroup = 'Toko Private';
    protected static ?string $slug = 'installment-plans';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tenor')
                    ->label('Tenor (Bulan)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('fee_percentage')
                    ->label('Fee Percentage')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->step(0.01)
                    ->default(0),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenor')
                    ->label('Tenor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fee_percentage')
                    ->label('Fee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('installments_count')
                    ->label('Installments')
                    ->counts('installments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->form([
                        Forms\Components\Toggle::make('active'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(isset($data['active']), function ($q) use ($data) {
                            $q->where('is_active', $data['active']);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => \App\Filament\Resources\InstallmentPlanResource\Pages\ListInstallmentPlans::route('/'),
            'create' => \App\Filament\Resources\InstallmentPlanResource\Pages\CreateInstallmentPlan::route('/create'),
            'view' => \App\Filament\Resources\InstallmentPlanResource\Pages\ViewInstallmentPlan::route('/{record}'),
            'edit' => \App\Filament\Resources\InstallmentPlanResource\Pages\EditInstallmentPlan::route('/{record}/edit'),
        ];
    }
}