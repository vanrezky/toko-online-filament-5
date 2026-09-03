<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Clusters\ContentCluster;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Constants\UploadPath;
use App\Enums\BlogPostStatus;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Override;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $cluster = ContentCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/page-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/page-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

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
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/page-resource.tabs.seo'))
                            ->schema([
                                TitleSchema::slug()
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn (?Page $record): bool => $record?->isRequiredLegalPage() ?? false),
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
                                TextInput::make('order')
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
                                    ->disabled(fn (?Page $record): bool => $record?->isRequiredLegalPage() ?? false)
                                    ->required(),
                                Toggle::make('show_in_menu')
                                    ->label(__('admin/page-resource.fields.show_in_menu'))
                                    ->default(false)
                                    ->live(),
                                Select::make('menu_location')
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
                                    ->disk(config('filesystems.upload_disk'))
                                    ->directory(UploadPath::IMAGES_UPLOAD_PATH),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin/page-resource.columns.title'))
                    ->searchable()
                    ->extraCellAttributes(['class' => 'text-wrap']),

                TextColumn::make('is_status')
                    ->label(__('admin/page-resource.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->date()
                    ->label(__('admin/page-resource.columns.published_at'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('admin/page-resource.columns.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('admin/page-resource.columns.updated_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(fn (Page $record): bool => ! $record->isRequiredLegalPage())
                        ->color('danger'),
                ])
            ])
            ->checkIfRecordIsSelectableUsing(fn (Page $record): bool => ! $record->isRequiredLegalPage())
            ->toolbarActions([
                DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
