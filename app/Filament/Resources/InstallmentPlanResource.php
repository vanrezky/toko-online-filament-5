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
    protected static ?string $navigationGroup = 'Master';
    protected static ?string $slug = 'installment-plans';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'tenor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tenor Cicilan')
                    ->description('Atur jangka waktu dan biaya cicilan')
                    ->schema([
                        Forms\Components\TextInput::make('tenor')
                            ->label('Tenor (Bulan)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('fee_percentage')
                            ->label('Fee / Bunga (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->disabled(fn(?InstallmentPlan $record) => $record?->installments()->exists())
                            ->helperText(fn(?InstallmentPlan $record) => $record?->installments()->exists() ? 'Tidak bisa dinonaktifkan — sudah ada cicilan yang menggunakan tenor ini' : null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenor')
                    ->label('Tenor')
                    ->formatStateUsing(fn(int $state) => $state . ' bulan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fee_percentage')
                    ->label('Fee/Bunga')
                    ->formatStateUsing(fn(float $state) => $state . '%')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installments_count')
                    ->label('Cicilan Aktif')
                    ->counts('installments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(InstallmentPlan $record) => $record->installments()->exists())
                    ->tooltip(fn(InstallmentPlan $record) => $record->installments()->exists() ? 'Tidak bisa dihapus — sudah ada cicilan' : 'Hapus tenor'),
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