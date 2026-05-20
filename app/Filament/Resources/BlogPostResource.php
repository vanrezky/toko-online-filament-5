<?php

namespace App\Filament\Resources;

use App\Constants\UploadPath;
use App\Enums\BlogPostStatus;
use App\Filament\Resources\BlogPostResource\Pages;
use App\Filament\Resources\BlogPostResource\RelationManagers;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Override;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'blog/posts';

    public static function getNavigationLabel(): string
    {
        return __('admin/blog-post-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/blog-post-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/blog-post-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/blog-post-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Services\NavigationBadgeCache::getBlogPostCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->schema([
                        Tab::make(__('admin/blog-post-resource.tabs.title_and_content'))
                            ->schema([
                                TitleSchema::title()
                                    ->autofocus()
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/blog-post-resource.placeholders.post_title'))
                                    ->minLength(5)
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->id('post-title')
                                    ->extraInputAttributes(['class' => 'column-title'], true),
                                RichEditor::make('content')
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/blog-post-resource.placeholders.post_content'))
                                    ->required()
                                    ->string()
                                    ->columnSpanFull(),
                                Select::make('blog_category_id')
                                    ->label(__('admin/blog-post-resource.fields.category_id'))
                                    ->relationship('category', titleAttribute: 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Tab::make(__('admin/blog-post-resource.tabs.seo'))
                            ->schema(
                                [
                                    TitleSchema::slug()
                                        ->label(__('admin/blog-post-resource.fields.slug'))
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    TitleSchema::hidden(),
                                    MetaSchema::get(),
                                ]
                            ),
                        Tab::make(__('admin/blog-post-resource.tabs.tags'))
                            ->schema([
                                SpatieTagsInput::make('tags')
                                    ->label(__('admin/blog-post-resource.fields.tags'))
                                    ->placeholder(__('admin/blog-post-resource.placeholders.tags_placeholder'))
                            ]),
                        Tab::make(__('admin/blog-post-resource.tabs.visibility'))
                            ->schema([
                                Select::make('is_status')
                                    ->label(__('admin/blog-post-resource.fields.status'))
                                    ->helperText(__('admin/blog-post-resource.helpers.publish_draft'))
                                    ->options([
                                        BlogPostStatus::DRAFT->value => __('admin/blog-post-resource.status.draft'),
                                        BlogPostStatus::PUBLISHED->value => __('admin/blog-post-resource.status.published'),
                                    ])
                                    ->default(BlogPostStatus::PUBLISHED->value)
                                    ->native(false)
                                    ->required(),
                                DatePicker::make('published_at')
                                    ->helperText(__('admin/blog-post-resource.helpers.published_date'))
                                    ->default(now())
                            ]),
                        Tab::make(__('admin/blog-post-resource.tabs.image'))
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('admin/blog-post-resource.fields.featured_image'))
                                    ->image()
                                    ->imageEditor()
                                    ->directory(UploadPath::IMAGES_UPLOAD_PATH),
                            ]),

                    ])->columnSpanFull(),
                Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(fn (string $operation) => $operation !== 'edit')

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin/blog-post-resource.columns.title'))
                    ->searchable()
                    ->sortable()
                    ->words(5),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('admin/blog-post-resource.columns.category'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label(__('admin/blog-post-resource.columns.author'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->date()
                    ->label(__('admin/blog-post-resource.columns.published_at')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin/blog-post-resource.columns.updated_at'))
                    ->date(),
                Tables\Columns\TextColumn::make('is_status')
                    ->label(__('admin/blog-post-resource.columns.status'))
                    ->badge()

            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'view' => Pages\ViewBlogPost::route('/{record}'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
