<?php

namespace App\Filament\Resources\Faqs;

use App\Services\NavigationBadgeCache;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Models\Faq;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Override;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
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
        $count = NavigationBadgeCache::getFaqCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('question')
                            ->label(__('admin/faq-resource.fields.question'))
                            ->required()
                            ->maxLength(255),
                        RichEditor::make('answer')
                            ->label(__('admin/faq-resource.fields.answer'))
                            ->required()
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label(__('admin/faq-resource.columns.question'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin/faq-resource.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('admin/faq-resource.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
