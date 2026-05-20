<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Override;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'faqs';

    public static function getNavigationLabel(): string
    {
        return __('admin/faq-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/faq-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/faq-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/faq-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Services\NavigationBadgeCache::getFaqCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    Forms\Components\TextInput::make('question')
                        ->label(__('admin/faq-resource.fields.question'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\RichEditor::make('answer')
                        ->label(__('admin/faq-resource.fields.answer'))
                        ->required()
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label(__('admin/faq-resource.columns.question'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin/faq-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin/faq-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
