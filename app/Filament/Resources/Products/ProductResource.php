<?php

namespace App\Filament\Resources\Products;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use Exception;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Constants\Status;
use App\Constants\UploadPath;
use App\Filament\Resources\Products\RelationManagers\ProductVariantsRelationManager;
use App\Filament\Resources\Schema\MetaSchema;
use App\Filament\Resources\Schema\TitleSchema;
use App\Models\Product;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

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
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make(__('admin/product-resource.tabs.code_and_category'))
                            ->schema(
                                [
                                    TextInput::make('code')
                                        ->label(__('admin/product-resource.fields.code'))
                                        ->helperText(__('admin/product-resource.fields.code_helper'))
                                        ->required()
                                        ->maxLength(20)
                                        ->columnSpanFull()
                                        ->unique(column: 'code', ignoreRecord: true)
                                        ->maxLength('20')
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
                                        ->relationship('category', 'name', fn(Builder $query): Builder => $query->active())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpanFull(),
                                    Select::make('digital')
                                        ->default(false)
                                        ->hidden()
                                        ->label(__('admin/product-resource.fields.digital'))
                                        ->helperText(__('admin/product-resource.fields.digital_helper'))
                                        ->required()
                                        ->options([
                                            Status::PHYSICAL_PRODUCT => __('admin/product-resource.fields.physical_product'),
                                            Status::DIGITAL_PRODUCT => __('admin/product-resource.fields.digital_product'),
                                        ])
                                        ->native(false)
                                        ->default(Status::PHYSICAL_PRODUCT)
                                        ->live(onBlur: true)
                                        ->columnSpanFull(),
                                    TextInput::make('digital_url')
                                        ->placeholder(__('admin/product-resource.fields.digital_url_placeholder'))
                                        ->helperText(__('admin/product-resource.fields.digital_url_helper'))
                                        ->columnSpanFull()
                                        ->visible(fn(Get $get): bool => $get('digital'))
                                        ->required(fn(Get $get): bool => $get('digital'))
                                        ->url()
                                        ->columnSpanFull(),
                                ]
                            )->inlineLabel()->columns(2),
                        Tab::make(__('admin/product-resource.tabs.name_and_description'))
                            ->schema([
                                TitleSchema::title('name')
                                    ->label(__('admin/product-resource.fields.name'))
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/product-resource.fields.name'))
                                    ->helperText(__('admin/product-resource.fields.name_helper'))
                                    ->minLength(5)
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->id('product-name')
                                    ->extraInputAttributes(['class' => 'column-title'], true),
                                RichEditor::make('description')
                                    ->hiddenLabel()
                                    ->placeholder(__('admin/product-resource.fields.description'))
                                    ->helperText(__('admin/product-resource.fields.description_helper'))
                                    ->required()
                                    ->disableToolbarButtons([
                                        'attachFiles',
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('admin/product-resource.tabs.price'))
                            ->schema(
                                [
                                    TextInput::make('min_order')
                                        ->label(__('admin/product-resource.fields.min_order'))
                                        ->rules('nullable|numeric')
                                        ->helperText(__('admin/product-resource.fields.min_order_helper'))
                                        ->required()
                                        ->default(1)
                                        ->maxValue(fn(Get $get) => $get('stock')),
                                    TextInput::make('sale_price')
                                        ->rules('nullable|numeric')
                                        ->label(__('admin/product-resource.fields.sale_price'))
                                        ->helperText(__('admin/product-resource.fields.sale_price_helper'))
                                        ->rules([
                                            fn(Get $get, ?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                                $price = $get('price');
                                                if ($value !== null && $price !== null && (float) $value >= (float) $price) {
                                                    $fail(__('admin/product-resource.notifications.sale_price_error'));
                                                }
                                            },
                                        ]),
                                    TextInput::make('price')
                                        ->rules('numeric')
                                        ->label(__('admin/product-resource.fields.price'))
                                        ->helperText(__('admin/product-resource.fields.price_helper'))
                                        ->required()
                                        ->live(onBlur: true),
                                    TextInput::make('afiliate_price')
                                        ->rules('nullable|numeric')
                                        ->label(__('admin/product-resource.fields.afiliate_price'))
                                        ->helperText(__('admin/product-resource.fields.afiliate_price_helper'))
                                        ->hidden(),
                                ]
                            )->inlineLabel(),
                        Tab::make(__('admin/product-resource.tabs.inventory'))
                            ->schema([
                                TextInput::make('weight')
                                    ->rules('nullable|numeric')
                                    ->label(__('admin/product-resource.fields.weight'))
                                    ->helperText(__('admin/product-resource.fields.weight_helper'))
                                    ->visible(fn(Get $get): bool => ! $get('digital'))
                                    ->required(fn(Get $get): bool => ! $get('digital')),
                                Select::make('warehouse_id')
                                    ->label(__('admin/product-resource.fields.warehouse_id'))
                                    ->helperText(str(__('admin/product-resource.fields.warehouse_id_helper'))->inlineMarkdown()->toHtmlString())
                                    ->relationship('warehouse', 'name', fn(Builder $query): Builder => $query->active())
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn(Get $get): bool => ! $get('digital'))
                                    ->required(fn(Get $get): bool => ! $get('digital'))
                                    ->columnSpanFull(),
                                TextInput::make('stock')
                                    ->rules('nullable|numeric')
                                    ->helperText(__('admin/product-resource.fields.stock_helper'))
                                    ->required()
                                    ->default(1)
                                    ->live(),
                                TextInput::make('security_stock')
                                    ->rules('nullable|numeric')
                                    ->helperText(__('admin/product-resource.fields.security_stock_helper'))
                                    ->required()
                                    ->default(0)
                                    ->maxValue(fn(Get $get) => $get('stock')),
                            ])->inlineLabel(),
                        Tab::make(__('admin/product-resource.tabs.faqs'))
                            ->hidden()
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
                        Tab::make('Review & Rating')
                            ->schema([
                                TextInput::make('fake_sold_count')
                                    ->label('Penjualan pemancing')
                                    ->helperText('Penjualan riil dihitung otomatis dari transaksi completed.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                Repeater::make('adminReviews')
                                    ->relationship('adminReviews')
                                    ->label('Review admin')
                                    ->helperText('Hanya review yang dibuat admin yang ditampilkan di sini.')
                                    ->defaultItems(0)
                                    ->schema([
                                        TextInput::make('reviewer_name')->label('Nama pengulas')->required()->maxLength(255),
                                        TextInput::make('rating')->label('Rating')->numeric()->minValue(1)->maxValue(5)->required(),
                                        Textarea::make('review')->label('Ulasan')->rows(3)->maxLength(7000),
                                        Forms\Components\Hidden::make('is_admin')->default(true),
                                        SpatieMediaLibraryFileUpload::make('images')
                                            ->collection('images')
                                            ->image()
                                            ->multiple()
                                            ->maxFiles(3)
                                            ->maxSize(5120),
                                    ])->columns(2),
                            ])->columns(2),
                        Tab::make(__('admin/product-resource.tabs.seo'))
                            ->hidden()
                            ->schema([
                                TitleSchema::slug()
                                    ->required()
                                    ->maxLength(255)
                                    ->required()
                                    ->columnSpanFull(),
                                TitleSchema::hidden(),
                                MetaSchema::get(),
                            ]),
                    ])->columnSpanFull(),
                Section::make(__('admin/product-resource.tabs.images'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->image()
                            ->imageEditor()
                            ->required()
                            ->hiddenLabel()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(5)
                            ->imageCropAspectRatio('1:1')
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->downloadable()
                            ->panelLayout('grid')
                            ->disk(getActiveDisk())
                            ->rules(['required', 'mimes:png,jpg,jpeg,webp,gif'])
                            ->directory(UploadPath::PRODUCT_UPLOAD_PATH),
                    ])->columnSpanFull(),
                Section::make(__('admin/product-resource.fields.other_settings'))
                    ->hidden()
                    ->schema([
                        TagsInput::make('tags')
                            ->label(__('admin/product-resource.fields.tags'))
                            ->placeholder(__('admin/product-resource.fields.tags_placeholder'))
                            ->separator(','),
                        Select::make('is_active')
                            ->label(__('admin/product-resource.fields.is_active'))
                            ->helperText(__('admin/product-resource.fields.is_active_helper'))
                            ->options(self::getStatusOptions())
                            ->default(Status::ACTIVE)
                            ->hidden()
                            ->native(false)
                            ->required(),
                    ])->columnSpanFull(),
                // Tabs::make('Advanced Settings')
                //     ->schema([
                // @feature-toggle: reseller — uncomment to re-enable Reseller Price tab
                // Tabs\Tab::make('Reseller Price')
                //     ->schema([
                //         Forms\Components\Repeater::make('resellerPrices')
                //             ->relationship('resellerPrices')
                //             ->hiddenLabel()
                //             ->reorderable(false)
                //             ->deleteAction(function (Action $action) {
                //                 $action->requiresConfirmation();
                //             })
                //             ->defaultItems(0)
                //             ->schema([
                //                 Forms\Components\Select::make('reseller_id')
                //                     ->label(__('Reseller Level'))
                //                     ->options(Reseller::active()->get()->pluck('name_level', 'id')->toArray())
                //                     ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                //                     ->hintAction(
                //                         Action::make('wholesale')
                //                             ->icon('heroicon-m-currency-dollar')
                //                             ->label('Add wholesale')
                //                             ->form([
                //                                 Forms\Components\Repeater::make('wholesales')
                //                                     ->default(fn($record): array => $record ? $record->wholesales->toArray() : [])
                //                                     ->schema(self::getWholesalesSchema())
                //                                     ->hiddenLabel()
                //                                     ->grid(['lg' => 2]),
                //                             ])
                //                             ->action(fn(array $data, $record) => self::setActionWholesales($data, $record))
                //                             ->visible(fn($record): bool => ! empty($record))
                //                     ),
                //                 Forms\Components\TextInput::make('price')
                //                     ->required()
                //                     ->numeric()
                //                     ->default(fn(Get $get) => $get('../../price'))
                //                     ->live(onBlur: true)
                //                     ->hint(fn(Get $get): string => 'Normal Price: Rp ' . number_format($get('../../price') ?? 0, 0, ',', '.'))
                //             ])->grid(['md' => 2]),
                //     ]),
                //     Tab::make(__('admin/product-resource.tabs.wholesales'))
                //         ->label(__('admin/product-resource.tabs.wholesales'))
                //         ->hidden()
                //         ->schema([
                //             Repeater::make('wholesales')
                //                 ->label(__('admin/product-resource.tabs.wholesales_price'))
                //                 ->relationship('wholesales', fn(Builder $query): Builder => $query->whereNull('reseller_id'))
                //                 ->reorderable(false)
                //                 ->hiddenLabel()
                //                 // ->collapsible()
                //                 ->deleteAction(
                //                     fn(Action $action) => $action->requiresConfirmation(),
                //                 )
                //                 ->cloneable()
                //                 ->defaultItems(0)
                //                 ->schema(self::getWholesalesSchema())
                //                 ->grid(['xl' => 2]),
                //         ]),
                // ])->columnSpanFull(),
            ])->columns(3);
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
                    ->sortable()
                    ->hidden(),
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
                            $text = __('admin/product-resource.fields.created_from') . ' ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                            if ($data['created_until']) {
                                $text .= ' - ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                            }
                        }

                        return $text;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

            ])
            ->recordActions([
                ActionGroup::make([
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
            'edit' => EditProduct::route('/{record}/edit'),
            // 'view' => '',
        ];
    }

    public static function getIsFeaturedColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->label(__('admin/product-resource.columns.published'))
                ->afterStateUpdated(fn() => notification(__('admin/product-resource.notifications.published_updated'), 'success'));
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
                ->default(fn(Get $get) => $get('../../price'))
                ->live(onBlur: true)
                ->hint(fn(Get $get): string => __('admin/product-resource.fields.price') . ': Rp ' . number_format($get('../../price') ?? 0, 0, ',', '.'))
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
