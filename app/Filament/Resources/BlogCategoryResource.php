<?php

namespace App\Filament\Resources;

use App\Enums\StatusType;
use App\Filament\Resources\BlogCategoryResource\Pages;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\BlogCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'blog/categories';

    public static function getNavigationLabel(): string
    {
        return __('admin/blog-category-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/blog-category-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/blog-category-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/blog-category-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Services\NavigationBadgeCache::getBlogCategoryCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->schema([
                        Tabs\Tab::make(__('admin/blog-category-resource.tabs.main'))
                            ->schema([
                                TitleSchema::title('name')
                                    ->required()
                                    ->autofocus()
                                    ->minLength(3)
                                    ->maxLength(255),
                                RichEditor::make('description')->columnSpanFull(),
                                Toggle::make('is_visible')
                                    ->label(__('admin/blog-category-resource.fields.is_visible'))
                                    ->default(true)

                            ]),
                        Tabs\Tab::make(__('admin/blog-category-resource.tabs.seo'))
                            ->schema([
                                TitleSchema::slug(),
                                TitleSchema::hidden(),
                                MetaSchema::get(),
                            ])
                    ])->columnSpanFull()


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin/blog-category-resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('admin/blog-category-resource.columns.slug'))
                    ->searchable()
                    ->sortable(),
                self::getIsVisibleColumn(),
                TextColumn::make('updated_at')
                    ->label(__('admin/blog-category-resource.columns.updated_at'))
                    ->dateTime()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $delete = true;

                            foreach ($records as $record) {
                                if ($record->posts_count > 0) {
                                    $delete = false;
                                    break;
                                }
                            }

                            if (!$delete) {
                                return notification(__('admin/blog-category-resource.notifications.cannot_delete'), 'warning');
                            }

                            $records->each->delete();
                            return notification(__('admin/blog-category-resource.notifications.deleted'));
                        }),
                ]),
            ]);
    }

    public static function getIsVisibleColumn()
    {
        if (self::shouldCanUpdate()) {
            return Tables\Columns\ToggleColumn::make('is_visible')
                ->afterStateUpdated(fn() => notification(__('admin/blog-category-resource.notifications.visibility_updated'), 'success'))
                ->label(__('admin/blog-category-resource.fields.is_visible'));
        }

        return Tables\Columns\IconColumn::make('is_visible')->boolean()->label(__('admin/blog-category-resource.columns.is_visible'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_blog::category');
    }
}
