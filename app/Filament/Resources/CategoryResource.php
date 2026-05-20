<?php

namespace App\Filament\Resources;

use App\Constants\UploadPath;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('admin/category-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/category-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/category-resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin/category-resource.navigation_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->schema([
                        Tabs\Tab::make(__('admin/category-resource.tabs.main'))
                            ->schema([
                                Group::make([
                                    TitleSchema::title('name')
                                        ->label(__('admin/category-resource.fields.name'))
                                        ->required()
                                        ->minLength(3),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label(__('admin/category-resource.fields.is_active')),
                                    Forms\Components\Toggle::make('is_featured')
                                        ->label(__('admin/category-resource.fields.is_featured'))
                                ]),

                                SpatieMediaLibraryFileUpload::make('image')
                                    ->label(__('admin/category-resource.fields.image'))
                                    ->maxSize(1024)
                                    ->rules(['required', 'mimes:png,jpg,jpeg,webp,gif', 'max:1024'])
                                    ->image()
                                    ->directory(UploadPath::CATEGORY_UPLOAD_PATH)
                                    ->imageCropAspectRatio('1:1')
                                    ->imagePreviewHeight(250)
                                    ->helperText(__('admin/category-resource.fields.image_helper'))
                                    ->disk(getActiveDisk())
                            ])->columns(2),
                        Tabs\Tab::make(__('admin/category-resource.tabs.seo'))
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
                SpatieMediaLibraryImageColumn::make('image')->conversion('thumb')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin/category-resource.columns.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')
                    ->prefix(__('admin/category-resource.columns.products_prefix'))
                    ->badge()
                    ->icon('heroicon-o-squares-2x2')
                    ->label(__('admin/category-resource.columns.products_count'))
                    ->color('danger')
                    ->sortable(),
                self::getIsActiveColumn(),
                self::getIsFeaturedColumn(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()->action(function ($record) {
                        if ($record->products()->count()) {
                            return notification(__('admin/category-resource.notifications.cannot_delete'), 'warning');
                        }

                        return $record->delete();
                    }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->action(function ($records) {
                    $delete = true;
                    foreach ($records as $key => $record) {
                        if ($record->products()->count()) {
                            $delete = false;
                            break;
                        }
                    }

                    if (!$delete) {
                        return notification(__('admin/category-resource.notifications.cannot_delete_bulk'), 'warning');
                    }
                }),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return Tables\Columns\ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('admin/category-resource.notifications.activation_updated')))->label(__('admin/category-resource.columns.is_active'));
        }
        return Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('admin/category-resource.columns.is_active'));
    }

    public static function getIsFeaturedColumn()
    {
        if (self::shouldCanUpdate()) {
            return Tables\Columns\ToggleColumn::make('is_featured')
                ->afterStateUpdated(fn() => notification(__('admin/category-resource.notifications.featured_updated')))->label(__('admin/category-resource.columns.is_featured'));
        }
        return Tables\Columns\IconColumn::make('is_featured')->boolean()->label(__('admin/category-resource.columns.is_featured'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_category');
    }
}