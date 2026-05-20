<?php

namespace App\Filament\Resources;

use App\Constants\UploadPath;
use App\Enums\BlogPostStatus;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/page-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/page-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/page-resource.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Services\NavigationBadgeCache::getPageCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->schema([
                        Tab::make(__('admin/page-resource.tabs.title_and_content'))
                            ->schema([
                                TitleSchema::title('title')
                                    ->autofocus()
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/page-resource.placeholders.page_title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->id('page-title')
                                    ->extraInputAttributes(['class' => 'column-title'], true),
                                RichEditor::make('content')
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/page-resource.placeholders.page_content'))
                                    ->required()
                                    ->string()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/page-resource.tabs.seo'))
                            ->schema([
                                TitleSchema::slug()->required()->maxLength(255),
                                TitleSchema::hidden(),
                                MetaSchema::get(),
                            ]),
                        Tab::make(__('admin/page-resource.tabs.visibility'))
                            ->schema([
                                Select::make('parent_id')
                                    ->label(__('admin/page-resource.fields.parent_page'))
                                    ->options(function (?string $operation, ?string $state) {
                                        if ($operation === 'create') {
                                            return Page::all()->pluck('title', 'id');
                                        }
                                        return  Page::where('id', '!=', $state)->get()->pluck('title', 'id');
                                    })
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('order')
                                    ->label(__('admin/page-resource.fields.order'))
                                    ->required()
                                    ->default(1)
                                    ->numeric(),
                                Select::make('is_status')
                                    ->label(__('admin/page-resource.fields.status'))
                                    ->helperText(__('admin/page-resource.helpers.publish_draft'))
                                    ->options([
                                        BlogPostStatus::DRAFT->value => __('admin/page-resource.status.draft'),
                                        BlogPostStatus::PUBLISHED->value => __('admin/page-resource.status.published'),
                                    ])
                                    ->default(BlogPostStatus::PUBLISHED->value)
                                    ->native(false)
                                    ->required(),
                                Forms\Components\Toggle::make('show_in_menu')
                                    ->label(__('admin/page-resource.fields.show_in_menu'))
                                    ->default(false)
                                    ->live(),
                                Forms\Components\Select::make('menu_location')
                                    ->label(__('admin/page-resource.fields.menu_location'))
                                    ->options([
                                        'header' => __('admin/page-resource.menu_location.header'),
                                        'footer' => __('admin/page-resource.menu_location.footer'),
                                        'both' => __('admin/page-resource.menu_location.both'),
                                    ])
                                    ->required(fn (Get $get) => $get('show_in_menu'))
                                    ->disabled(fn (Get $get) => !$get('show_in_menu'))
                                    ->dehydrated(fn (Get $get) => $get('show_in_menu')),
                                DateTimePicker::make('published_at')
                                    ->helperText(__('admin/page-resource.helpers.published_date'))
                                    ->default(now())
                            ]),
                        Tab::make(__('admin/page-resource.tabs.image'))
                            ->schema([
                                FileUpload::make('image')
                                    ->label(__('admin/page-resource.fields.featured_image'))
                                    ->image()
                                    ->imageEditor()
                                    ->directory(UploadPath::IMAGES_UPLOAD_PATH),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin/page-resource.columns.title'))
                    ->searchable()
                    ->extraCellAttributes(['class' => 'text-wrap']),

                Tables\Columns\TextColumn::make('is_status')
                    ->label(__('admin/page-resource.columns.status'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->date()
                    ->label(__('admin/page-resource.columns.published_at'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('admin/page-resource.columns.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('admin/page-resource.columns.updated_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
