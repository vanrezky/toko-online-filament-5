<?php

namespace App\Filament\Resources\Products;

use App\Constants\Status;
use App\Constants\UploadPath;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\RelationManagers\ProductVariantsRelationManager;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\Product;
use App\Models\Reseller;
use Carbon\Carbon;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $slug = 'products';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin/product-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/product-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/product-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/product-resource.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('product-form')
                    ->tabs([
                        Tab::make(__('admin/product-resource.tabs.images'))
                            ->schema([
                                Section::make(__('admin/product-resource.sections.images'))
                                    ->description(__('admin/product-resource.sections.images_description'))
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->image()
                                            ->imageEditor()
                                            ->required(fn (Get $get): bool => empty($get('image_urls')))
                                            ->hiddenLabel()
                                            ->multiple()
                                            ->reorderable()
                                            ->maxFiles(5)
                                            ->imageCropAspectRatio('1:1')
                                            ->imageEditorAspectRatios(['1:1'])
                                            ->downloadable()
                                            ->panelLayout('grid')
                                            ->disk(getActiveDisk())
                                            ->rules(['mimes:png,jpg,jpeg,webp,gif'])
                                            ->directory(UploadPath::PRODUCT_UPLOAD_PATH),
                                    ]),
                                Section::make(__('admin/product-resource.sections.image_urls'))
                                    ->description(__('admin/product-resource.fields.image_urls_helper'))
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Repeater::make('image_urls')
                                            ->hiddenLabel()
                                            ->schema([
                                                TextInput::make('url')
                                                    ->label(__('admin/product-resource.fields.image_url'))
                                                    ->required()
                                                    ->url()
                                                    ->rules(['regex:/^https?:\/\//i'])
                                                    ->maxLength(2048)
                                                    ->live(onBlur: true),
                                            ])
                                            ->defaultItems(0)
                                            ->maxItems(5)
                                            ->addActionLabel(__('admin/product-resource.actions.add_image_url'))
                                            ->rules([
                                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                                    $uploadedImageCount = count(array_filter($get('images') ?? []));
                                                    $linkedImageCount = collect($get('image_urls') ?? [])
                                                        ->filter(fn (array $image): bool => filled($image['url'] ?? null))
                                                        ->count();

                                                    if ($uploadedImageCount + $linkedImageCount > 5) {
                                                        $fail(__('admin/product-resource.notifications.image_limit_exceeded'));
                                                    }
                                                },
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('admin/product-resource.tabs.product_information'))
                            ->schema([
                                Section::make(__('admin/product-resource.sections.product_details'))
                                    ->schema([
                                        TitleSchema::title('name')
                                            ->label(__('admin/product-resource.fields.name'))
                                            ->placeholder(__('admin/product-resource.fields.name'))
                                            ->helperText(__('admin/product-resource.fields.name_helper'))
                                            ->minLength(5)
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->id('product-name')
                                            ->extraInputAttributes(['class' => 'column-title'], true),
                                        RichEditor::make('description')
                                            ->label(__('admin/product-resource.fields.description'))
                                            ->placeholder(__('admin/product-resource.fields.description'))
                                            ->helperText(__('admin/product-resource.fields.description_helper'))
                                            ->required()
                                            ->disableToolbarButtons(['attachFiles'])
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('admin/product-resource.sections.classification'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('admin/product-resource.fields.code'))
                                            ->helperText(__('admin/product-resource.fields.code_helper'))
                                            ->required()
                                            ->maxLength(20)
                                            ->unique(column: 'code', ignoreRecord: true)
                                            ->suffixAction(
                                                Action::make('generateCode')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->tooltip(__('admin/product-resource.notifications.generate_code'))
                                                    ->action(function (Set $set) {
                                                        $set('code', self::generateProductCode());
                                                    })
                                            ),
                                        Select::make('category_id')
                                            ->label(__('admin/product-resource.fields.category_id'))
                                            ->helperText(__('admin/product-resource.fields.category_id_helper'))
                                            ->relationship('category', 'name', fn (Builder $query): Builder => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('digital')
                                            ->label(__('admin/product-resource.fields.digital'))
                                            ->helperText(__('admin/product-resource.fields.digital_helper'))
                                            ->required()
                                            ->options([
                                                Status::PHYSICAL_PRODUCT => __('admin/product-resource.fields.physical_product'),
                                                Status::DIGITAL_PRODUCT => __('admin/product-resource.fields.digital_product'),
                                            ])
                                            ->native(false)
                                            ->default(Status::PHYSICAL_PRODUCT)
                                            ->live(onBlur: true),
                                        Select::make('is_active')
                                            ->label(__('admin/product-resource.fields.is_active'))
                                            ->helperText(__('admin/product-resource.fields.is_active_helper'))
                                            ->options(self::getStatusOptions())
                                            ->default(Status::ACTIVE)
                                            ->native(false)
                                            ->required(),
                                        TextInput::make('digital_url')
                                            ->label(__('admin/product-resource.fields.digital_url'))
                                            ->placeholder(__('admin/product-resource.fields.digital_url_placeholder'))
                                            ->helperText(__('admin/product-resource.fields.digital_url_helper'))
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get): bool => $get('digital'))
                                            ->required(fn (Get $get): bool => $get('digital'))
                                            ->url()
                                            ->columnSpanFull(),
                                    ])->columns(2),
                                Section::make(__('admin/product-resource.sections.tags'))
                                    ->schema([
                                        TagsInput::make('tags')
                                            ->label(__('admin/product-resource.fields.tags'))
                                            ->placeholder(__('admin/product-resource.fields.tags_placeholder'))
                                            ->separator(','),
                                    ]),
                            ]),
                        Tab::make(__('admin/product-resource.tabs.pricing_inventory'))
                            ->schema([
                                Section::make(__('admin/product-resource.sections.pricing'))
                                    ->schema([
                                        TextInput::make('price')
                                            ->rules('numeric')
                                            ->label(__('admin/product-resource.fields.price'))
                                            ->helperText(__('admin/product-resource.fields.price_helper'))
                                            ->required()
                                            ->live(onBlur: true),
                                        TextInput::make('sale_price')
                                            ->rules('nullable|numeric')
                                            ->label(__('admin/product-resource.fields.sale_price'))
                                            ->helperText(__('admin/product-resource.fields.sale_price_helper'))
                                            ->rules([
                                                fn (Get $get, ?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                    $price = $get('price');
                                                    if ($value !== null && $price !== null && (float) $value >= (float) $price) {
                                                        $fail(__('admin/product-resource.notifications.sale_price_error'));
                                                    }
                                                },
                                            ]),
                                        TextInput::make('afiliate_price')
                                            ->rules('nullable|numeric')
                                            ->label(__('admin/product-resource.fields.afiliate_price'))
                                            ->helperText(__('admin/product-resource.fields.afiliate_price_helper')),
                                        TextInput::make('min_order')
                                            ->label(__('admin/product-resource.fields.min_order'))
                                            ->rules('nullable|numeric')
                                            ->helperText(__('admin/product-resource.fields.min_order_helper'))
                                            ->required()
                                            ->default(1)
                                            ->maxValue(fn (Get $get) => $get('stock')),
                                    ])->columns(2),
                                Section::make(__('admin/product-resource.sections.inventory'))
                                    ->schema([
                                        TextInput::make('stock')
                                            ->rules('nullable|numeric')
                                            ->label(__('admin/product-resource.fields.stock'))
                                            ->helperText(__('admin/product-resource.fields.stock_helper'))
                                            ->required()
                                            ->default(1)
                                            ->live(),
                                        TextInput::make('security_stock')
                                            ->rules('nullable|numeric')
                                            ->label(__('admin/product-resource.fields.security_stock'))
                                            ->helperText(__('admin/product-resource.fields.security_stock_helper'))
                                            ->required()
                                            ->default(0)
                                            ->maxValue(fn (Get $get) => $get('stock')),
                                        TextInput::make('weight')
                                            ->rules('nullable|numeric')
                                            ->label(__('admin/product-resource.fields.weight'))
                                            ->helperText(__('admin/product-resource.fields.weight_helper'))
                                            ->visible(fn (Get $get): bool => ! $get('digital'))
                                            ->required(fn (Get $get): bool => ! $get('digital')),
                                        Select::make('warehouse_id')
                                            ->label(__('admin/product-resource.fields.warehouse_id'))
                                            ->helperText(str(__('admin/product-resource.fields.warehouse_id_helper'))->inlineMarkdown()->toHtmlString())
                                            ->relationship('warehouse', 'name', fn (Builder $query): Builder => $query->active())
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn (Get $get): bool => ! $get('digital'))
                                            ->required(fn (Get $get): bool => ! $get('digital')),
                                    ])->columns(2),
                            ]),
                        Tab::make(__('admin/product-resource.tabs.sales_content'))
                            ->schema([
                                Section::make(__('admin/product-resource.sections.reseller_pricing'))
                                    ->schema([
                                        Repeater::make('resellerPrices')
                                            ->relationship('resellerPrices')
                                            ->hiddenLabel()
                                            ->reorderable(false)
                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                            ->defaultItems(0)
                                            ->schema([
                                                Select::make('reseller_id')
                                                    ->label(__('admin/product-resource.fields.reseller_id'))
                                                    ->options(Reseller::active()->get()->pluck('name_level', 'id')->toArray())
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                    ->hintAction(
                                                        Action::make('wholesale')
                                                            ->icon('heroicon-m-currency-dollar')
                                                            ->label(__('admin/product-resource.actions.add_wholesale'))
                                                            ->form([
                                                                Repeater::make('wholesales')
                                                                    ->default(fn ($record): array => $record ? $record->wholesales->toArray() : [])
                                                                    ->schema(self::getWholesalesSchema())
                                                                    ->hiddenLabel()
                                                                    ->grid(['lg' => 2]),
                                                            ])
                                                            ->action(fn (array $data, $record) => self::setActionWholesales($data, $record))
                                                            ->visible(fn ($record): bool => ! empty($record))
                                                    ),
                                                TextInput::make('price')
                                                    ->label(__('admin/product-resource.fields.price'))
                                                    ->required()
                                                    ->numeric()
                                                    ->default(fn (Get $get) => $get('../../price'))
                                                    ->live(onBlur: true)
                                                    ->hint(fn (Get $get): string => __('admin/product-resource.fields.normal_price').': Rp '.number_format($get('../../price') ?? 0, 0, ',', '.')),
                                            ])->grid(['md' => 2]),
                                    ]),
                                Section::make(__('admin/product-resource.sections.wholesale_pricing'))
                                    ->schema([
                                        Repeater::make('wholesales')
                                            ->label(__('admin/product-resource.tabs.wholesales_price'))
                                            ->relationship('wholesales', fn (Builder $query): Builder => $query->whereNull('reseller_id'))
                                            ->reorderable(false)
                                            ->hiddenLabel()
                                            ->deleteAction(fn (Action $action) => $action->requiresConfirmation())
                                            ->cloneable()
                                            ->defaultItems(0)
                                            ->schema(self::getWholesalesSchema())
                                            ->grid(['xl' => 2]),
                                    ]),
                                Section::make(__('admin/product-resource.sections.faqs'))
                                    ->schema([
                                        Repeater::make('faqs')
                                            ->relationship('faqs')
                                            ->reorderable(true)
                                            ->hiddenLabel()
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('question')
                                                    ->label(__('admin/product-resource.fields.question'))
                                                    ->required(),
                                                Textarea::make('answer')
                                                    ->label(__('admin/product-resource.fields.answer'))
                                                    ->required(),
                                            ]),
                                    ]),
                                Section::make(__('admin/product-resource.sections.reviews'))
                                    ->schema([
                                        TextInput::make('fake_sold_count')
                                            ->label(__('admin/product-resource.fields.fake_sold_count'))
                                            ->helperText(__('admin/product-resource.fields.fake_sold_count_helper'))
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        Repeater::make('adminReviews')
                                            ->relationship('adminReviews')
                                            ->label(__('admin/product-resource.fields.admin_reviews'))
                                            ->helperText(__('admin/product-resource.fields.admin_reviews_helper'))
                                            ->defaultItems(0)
                                            ->schema([
                                                TextInput::make('reviewer_name')->label(__('admin/product-resource.fields.reviewer_name'))->required()->maxLength(255),
                                                TextInput::make('rating')->label(__('admin/product-resource.fields.rating'))->numeric()->minValue(1)->maxValue(5)->required(),
                                                Textarea::make('review')->label(__('admin/product-resource.fields.review'))->rows(3)->maxLength(7000),
                                                Forms\Components\Hidden::make('is_admin')->default(true),
                                                SpatieMediaLibraryFileUpload::make('images')
                                                    ->collection('images')
                                                    ->image()
                                                    ->multiple()
                                                    ->maxFiles(3)
                                                    ->maxSize(5120),
                                            ])->columns(2),
                                    ])->columns(2),
                            ]),
                        Tab::make(__('admin/product-resource.tabs.seo'))
                            ->schema([
                                Section::make(__('admin/product-resource.sections.seo'))
                                    ->schema([
                                        TitleSchema::slug()
                                            ->label(__('admin/product-resource.fields.slug'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TitleSchema::hidden(),
                                        MetaSchema::get(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->square()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText(size: 'lg')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->conversion('thumb'),
                TextColumn::make('name')
                    ->searchable()
                    // ->limit('50')
                    ->extraAttributes(['class' => 'text-wrap']),
                TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('admin/product-resource.columns.code'))
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('afiliate_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('variation')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subvariation')
                    ->toggleable(isToggledHiddenByDefault: true),
                self::getIsFeaturedColumn(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('admin/product-resource.fields.is_active'))
                    ->options(self::getStatusOptions()),
                SelectFilter::make('category_id')
                    ->label(__('admin/product-resource.fields.category_id'))
                    ->relationship('category', titleAttribute: 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->native(false),
                        DatePicker::make('created_until')->native(false),
                    ])->columns(2)
                    ->indicateUsing(function (array $data): ?string {
                        $text = null;
                        if ($data['created_from']) {
                            $text = __('admin/product-resource.fields.created_from').' '.Carbon::parse($data['created_from'])->toFormattedDateString();
                            if ($data['created_until']) {
                                $text .= ' - '.Carbon::parse($data['created_until'])->toFormattedDateString();
                            }
                        }

                        return $text;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label(__('admin/product-resource.actions.view')),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/product-resource.sections.images'))
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('images')
                            ->label(__('admin/product-resource.entries.images'))
                            ->conversion('thumb')
                            ->imageSize(120)
                            ->square()
                            ->limit(5)
                            ->limitedRemainingText(),
                    ]),
                Section::make(__('admin/product-resource.sections.product_details'))
                    ->schema([
                        TextEntry::make('name')->label(__('admin/product-resource.fields.name')),
                        TextEntry::make('code')->label(__('admin/product-resource.fields.code'))->badge(),
                        TextEntry::make('category.name')->label(__('admin/product-resource.fields.category_id')),
                        TextEntry::make('digital')
                            ->label(__('admin/product-resource.fields.digital'))
                            ->formatStateUsing(fn (int $state): string => $state === Status::DIGITAL_PRODUCT
                                ? __('admin/product-resource.fields.digital_product')
                                : __('admin/product-resource.fields.physical_product')),
                        TextEntry::make('is_active')
                            ->label(__('admin/product-resource.fields.is_active'))
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn (bool $state): string => $state
                                ? __('admin/product-resource.status.published')
                                : __('admin/product-resource.status.draft')),
                        TextEntry::make('digital_url')
                            ->label(__('admin/product-resource.fields.digital_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->visible(fn (Product $record): bool => $record->digital === Status::DIGITAL_PRODUCT)
                            ->columnSpanFull(),
                        TextEntry::make('tags')
                            ->label(__('admin/product-resource.fields.tags'))
                            ->formatStateUsing(fn ($state): string => collect($state ?? [])->implode(', '))
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label(__('admin/product-resource.fields.description'))
                            ->html()
                            ->columnSpanFull(),
                    ])->columns(2),
                Section::make(__('admin/product-resource.sections.pricing_inventory'))
                    ->schema([
                        TextEntry::make('price')->label(__('admin/product-resource.fields.price'))->money('IDR'),
                        TextEntry::make('sale_price')->label(__('admin/product-resource.fields.sale_price'))->money('IDR')->placeholder('-'),
                        TextEntry::make('afiliate_price')->label(__('admin/product-resource.fields.afiliate_price'))->money('IDR')->placeholder('-'),
                        TextEntry::make('min_order')->label(__('admin/product-resource.fields.min_order')),
                        TextEntry::make('stock')->label(__('admin/product-resource.fields.stock')),
                        TextEntry::make('security_stock')->label(__('admin/product-resource.fields.security_stock')),
                        TextEntry::make('warehouse.name')
                            ->label(__('admin/product-resource.fields.warehouse_id'))
                            ->visible(fn (Product $record): bool => $record->digital !== Status::DIGITAL_PRODUCT),
                        TextEntry::make('weight')
                            ->label(__('admin/product-resource.fields.weight'))
                            ->suffix(' g')
                            ->visible(fn (Product $record): bool => $record->digital !== Status::DIGITAL_PRODUCT),
                    ])->columns(3),
                Section::make(__('admin/product-resource.sections.reseller_pricing'))
                    ->schema([
                        RepeatableEntry::make('resellerPrices')
                            ->label(__('admin/product-resource.sections.reseller_pricing'))
                            ->schema([
                                TextEntry::make('reseller.name_level')->label(__('admin/product-resource.fields.reseller_id')),
                                TextEntry::make('price')->label(__('admin/product-resource.fields.price'))->money('IDR'),
                                RepeatableEntry::make('wholesales')
                                    ->label(__('admin/product-resource.sections.wholesale_pricing'))
                                    ->schema([
                                        TextEntry::make('min_qty')->label(__('admin/product-resource.fields.min_qty')),
                                        TextEntry::make('price')->label(__('admin/product-resource.fields.price_per_item'))->money('IDR'),
                                    ])
                                    ->columns(2)
                                    ->contained(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ]),
                Section::make(__('admin/product-resource.sections.wholesale_pricing'))
                    ->schema([
                        RepeatableEntry::make('normalWholesales')
                            ->label(__('admin/product-resource.sections.wholesale_pricing'))
                            ->getStateUsing(fn (Product $record): array => $record->wholesales
                                ->whereNull('reseller_id')
                                ->values()
                                ->all())
                            ->schema([
                                TextEntry::make('min_qty')->label(__('admin/product-resource.fields.min_qty')),
                                TextEntry::make('price')->label(__('admin/product-resource.fields.price_per_item'))->money('IDR'),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ]),
                Section::make(__('admin/product-resource.sections.faqs'))
                    ->schema([
                        RepeatableEntry::make('faqs')
                            ->label(__('admin/product-resource.sections.faqs'))
                            ->schema([
                                TextEntry::make('question')->label(__('admin/product-resource.fields.question')),
                                TextEntry::make('answer')->label(__('admin/product-resource.fields.answer'))->html()->columnSpanFull(),
                            ])
                            ->contained(false),
                    ]),
                Section::make(__('admin/product-resource.sections.reviews'))
                    ->schema([
                        TextEntry::make('fake_sold_count')->label(__('admin/product-resource.fields.fake_sold_count')),
                        RepeatableEntry::make('adminReviews')
                            ->label(__('admin/product-resource.fields.admin_reviews'))
                            ->schema([
                                TextEntry::make('reviewer_name')->label(__('admin/product-resource.fields.reviewer_name')),
                                TextEntry::make('rating')->label(__('admin/product-resource.fields.rating'))->badge(),
                                TextEntry::make('review')->label(__('admin/product-resource.fields.review'))->columnSpanFull(),
                                SpatieMediaLibraryImageEntry::make('images')
                                    ->label(__('admin/product-resource.entries.review_images'))
                                    ->collection('images')
                                    ->conversion('thumb')
                                    ->imageSize(80)
                                    ->square()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ]),
                Section::make(__('admin/product-resource.sections.seo'))
                    ->schema([
                        TextEntry::make('slug')->label(__('admin/product-resource.fields.slug')),
                        TextEntry::make('meta.title')->label(__('admin/product-resource.entries.meta_title'))->placeholder('-'),
                        TextEntry::make('meta.description')->label(__('admin/product-resource.entries.meta_description'))->placeholder('-')->columnSpanFull(),
                        TextEntry::make('meta.keyword')->label(__('admin/product-resource.entries.meta_keywords'))->placeholder('-')->columnSpanFull(),
                    ])->columns(2),
            ])
            ->columns(1);
    }

    public static function getRelations(): array
    {
        return [
            ProductVariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getIsFeaturedColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->label(__('admin/product-resource.columns.published'))
                ->afterStateUpdated(fn () => notification(__('admin/product-resource.notifications.published_updated'), 'success'));
        }

        return IconColumn::make('is_active')->boolean()->label(__('admin/product-resource.columns.published'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_product');
    }

    public static function getWholesalesSchema(): array
    {
        return [
            TextInput::make('min_qty')
                ->label(__('admin/product-resource.fields.min_qty'))
                ->required()
                ->default(0)
                ->distinct(),
            TextInput::make('price')
                ->label(__('admin/product-resource.fields.price_per_item'))
                ->required()
                ->default(fn (Get $get) => $get('../../price'))
                ->live(onBlur: true)
                ->hint(fn (Get $get): string => __('admin/product-resource.fields.price').': Rp '.number_format($get('../../price') ?? 0, 0, ',', '.'))
                ->distinct(),
        ];
    }

    public static function setActionWholesales($data, $record): Notification
    {
        try {
            $wholesales = [];
            foreach ($data['wholesales'] as $key => $who) {
                $wholesales[] = [
                    'reseller_id' => $record->reseller_id,
                    'product_id' => $record->product_id,
                    'min_qty' => $who['min_qty'],
                    'price' => $who['price'],
                ];
            }

            $record->wholesales()->delete();
            $record->wholesales()->createMany($wholesales);

            return notification(__('admin/product-resource.notifications.wholesale_submited'), 'success');
        } catch (Exception $e) {
            return notification(__('admin/product-resource.notifications.wholesale_failed'), 'danger');
        }
    }

    public static function getStatusOptions(): array
    {
        return [
            Status::INACTIVE => __('admin/product-resource.status.draft'),
            Status::ACTIVE => __('admin/product-resource.status.published'),
        ];
    }

    public static function generateProductCode(): string
    {
        $numbers = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $letters = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        return "PROD-{$numbers}-{$letters}";
    }

    public static function saveProductVariants(Model $record, Get $get, array $state, string $context): void
    {
        $product = $record;
        $productVariants = $state;
        // delete unuserd variants
        // $product->productVariants()->whereNot('id', array_column($productVariants, 'id'))->delete();

        foreach ($productVariants as $variantData) {
            $variant = $product->productVariants()->updateOrCreate(
                ['id' => $variantData['id'] ?? null],  // Update jika ID ada, jika tidak buat baru
                [
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                ]
            );

            // Handle the image upload
            if (! empty($variantData['image']) && is_array($variantData['image'])) {
                // Extract the first file object from the image array
                $imageData = array_values($variantData['image'])[0];

                if ($imageData instanceof TemporaryUploadedFile) {
                    // Move the temporary file to the active disk
                    $filePath = $imageData->store('/', getActiveDisk());

                    // Remove old media if needed
                    $variant->clearMediaCollection('default');

                    // Attach new media
                    $variant->addMediaFromDisk($filePath, getActiveDisk())
                        ->toMediaCollection();
                }
            }
            // Simpan attribute untuk variant
            $variant->attributes()->updateOrCreate(
                [
                    'attribute_name' => $product->variant,
                ],
                [
                    'attribute_value' => $variantData['variant'],
                ]
            );

            // Simpan attribute untuk sub-variant jika ada
            if (! empty($variantData['sub_variant'])) {
                $variant->attributes()->updateOrCreate(
                    [
                        'attribute_name' => $product->sub_variant,
                    ],
                    [
                        'attribute_value' => $variantData['sub_variant'],
                    ]
                );
            }
        }
    }
}
