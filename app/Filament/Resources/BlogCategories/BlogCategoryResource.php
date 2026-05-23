<?php

namespace App\Filament\Resources\BlogCategories;

use App\Services\NavigationBadgeCache;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use App\Filament\Resources\BlogCategories\Pages\ListBlogCategories;
use App\Filament\Resources\BlogCategories\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategories\Pages\EditBlogCategory;
use App\Enums\StatusType;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\BlogCategory;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

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
        $count = NavigationBadgeCache::getBlogCategoryCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->schema([
                        Tab::make(__('admin/blog-category-resource.tabs.main'))
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
                        Tab::make(__('admin/blog-category-resource.tabs.seo'))
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
            return ToggleColumn::make('is_visible')
                ->afterStateUpdated(fn() => notification(__('admin/blog-category-resource.notifications.visibility_updated'), 'success'))
                ->label(__('admin/blog-category-resource.fields.is_visible'));
        }

        return IconColumn::make('is_visible')->boolean()->label(__('admin/blog-category-resource.columns.is_visible'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_blog::category');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogCategories::route('/'),
            'create' => CreateBlogCategory::route('/create'),
            'edit' => EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
