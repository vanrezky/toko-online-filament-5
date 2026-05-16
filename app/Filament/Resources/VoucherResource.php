<?php

namespace App\Filament\Resources;

use App\Constants\UploadPath;
use App\Enums\VoucherDiscountType;
use App\Enums\VoucherProductType;
use App\Enums\VoucherType;
use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';
    protected static ?string $slug = 'vouchers';
    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin/voucher-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/voucher-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/voucher-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/voucher-resource.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('admin/voucher-resource.sections.voucher_information'))
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('admin/voucher-resource.fields.name'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label(__('admin/voucher-resource.fields.description'))
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\Group::make([
                                Forms\Components\Select::make('voucher_type')
                                    ->options([
                                        VoucherType::PRODUCT->value => __('admin/voucher-resource.fields.voucher_for_product'),
                                        VoucherType::SHIPPING_COST->value => __('admin/voucher-resource.fields.voucher_for_shipping_cost')
                                    ])
                                    ->default(VoucherType::PRODUCT->value)
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('product_type', $state == VoucherType::PRODUCT->value ? VoucherProductType::ALL_PRODUCT->value : VoucherProductType::PHYSICAL_PRODUCT->value);
                                    }),
                                Forms\Components\Select::make('discount_type')
                                    ->options([
                                        VoucherDiscountType::FIXED->value => __('admin/voucher-resource.fields.fixed'),
                                        VoucherDiscountType::PERCENTAGE->value => __('admin/voucher-resource.fields.percentage'),
                                    ])
                                    ->default(VoucherDiscountType::FIXED->value)
                                    ->required()
                                    ->live(),
                                Forms\Components\Select::make('product_type')
                                    ->options([
                                        VoucherProductType::ALL_PRODUCT->value => __('admin/voucher-resource.fields.all_product'),
                                        VoucherProductType::PHYSICAL_PRODUCT->value => __('admin/voucher-resource.fields.physical_product'),
                                        VoucherProductType::DIGITAL_PRODUCT->value => __('admin/voucher-resource.fields.digital_product'),
                                    ])
                                    ->default(VoucherProductType::ALL_PRODUCT->value)
                                    ->disabled(fn(Get $get) => $get('voucher_type') == VoucherType::SHIPPING_COST->value)
                                    ->required(),
                            ])->columns(3),
                            Forms\Components\TextInput::make('code')
                                ->label(__('admin/voucher-resource.fields.code'))
                                ->required()
                                ->maxLength(10),
                            Forms\Components\Group::make([
                                Forms\Components\TextInput::make('discount_min')
                                    ->label(__('admin/voucher-resource.fields.discount_min'))
                                    ->required()
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),

                                Forms\Components\TextInput::make('discount')
                                    ->label(fn(Get $get) => $get('discount_type') == VoucherDiscountType::FIXED->value ? __('admin/voucher-resource.fields.discount') : __('admin/voucher-resource.fields.discount_percentage'))
                                    ->required()
                                    ->rules('numeric')
                                    ->maxLength(fn(Get $get) => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value ? 100 : null)
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                                Forms\Components\TextInput::make('discount_max')
                                    ->label(__('admin/voucher-resource.fields.discount_max'))
                                    ->required(fn(Get $get): bool => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value)
                                    ->visible(fn(Get $get): bool => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value)
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                            ])->columns(3)
                        ]),

                ])->columnSpan(2),
                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('admin/voucher-resource.sections.validity_period'))
                        ->schema([
                            Forms\Components\DatePicker::make('start_at')
                                ->label(__('admin/voucher-resource.fields.start_at'))
                                ->required()
                                ->minDate(
                                    function (string $operation, ?string $state) {
                                        if ($operation === 'edit') {
                                            return $state;
                                        }

                                        return now()->format('Y-m-d');
                                    }
                                )
                                ->native(false)
                                ->live(),
                            Forms\Components\DatePicker::make('end_at')
                                ->label(__('admin/voucher-resource.fields.end_at'))
                                ->required()
                                ->native(false)
                                ->minDate(fn(Get $get): ?string => $get('start_at')),
                        ]),
                    Forms\Components\Section::make(__('admin/voucher-resource.sections.other_information'))
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('image')
                                ->label(__('admin/voucher-resource.fields.image'))
                                ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                                ->maxSize(1024)
                                ->image()
                                ->columnSpanFull()
                                ->imageCropAspectRatio('1:1')
                                ->helperText(__('admin/voucher-resource.fields.image_helper'))
                                ->directory(UploadPath::VOUCHER_UPLOAD_PATH)
                                ->disk(getActiveDisk()),
                            Forms\Components\Select::make('category_id')
                                ->label(__('admin/voucher-resource.fields.category_id'))
                                ->placeholder(__('admin/voucher-resource.fields.all_category'))
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Toggle::make('is_public')
                                ->label(__('admin/voucher-resource.fields.is_public'))
                                ->default(true)
                                ->required(),
                            Forms\Components\Toggle::make('is_active')
                                ->label(__('admin/voucher-resource.fields.is_active'))
                                ->default(true)
                                ->required(),
                            Forms\Components\TextInput::make('max_user_used')
                                ->label(__('admin/voucher-resource.fields.max_user_used'))
                                ->required()
                                ->numeric()
                                ->default(1),

                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin/voucher-resource.columns.name'))
                    ->icon(fn(Voucher $record): string => $record->is_public ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->iconColor(fn(Voucher $record): string => $record->is_public ? 'success' : 'warning')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('admin/voucher-resource.columns.code'))
                    ->weight('bold')
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('voucher_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('discount_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('product_type')
                    ->badge(),
                Tables\Columns\TextColumn::make('discount')
                    ->money(currency: '   ')
                    ->badge()
                    ->icon(fn(Voucher $record): string => match ($record->discount_type) {
                        VoucherDiscountType::FIXED => 'heroicon-o-tag',
                        VoucherDiscountType::PERCENTAGE => 'heroicon-o-receipt-percent',
                    })
                    ->color(fn(Voucher $record): string => $record->discount_type->value == VoucherDiscountType::FIXED->value ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('validity_period')
                    ->sortable(
                        query: fn(string $direction, $query) => $query->orderBy('start_at', $direction)
                    ),
                self::getIsActiveColumn(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
                    Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return Tables\Columns\ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('admin/voucher-resource.notifications.activation_updated'), 'success'))
                ->label(__('admin/voucher-resource.columns.active'));
        }

        return Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('admin/voucher-resource.columns.active'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_voucher');
    }
}
