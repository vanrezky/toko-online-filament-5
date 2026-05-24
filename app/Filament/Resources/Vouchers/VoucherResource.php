<?php

namespace App\Filament\Resources\Vouchers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Filament\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Resources\Vouchers\Pages\EditVoucher;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use App\Constants\UploadPath;
use App\Enums\VoucherDiscountType;
use App\Enums\VoucherProductType;
use App\Enums\VoucherType;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scissors';
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Group::make([
                    Section::make(__('admin/voucher-resource.sections.voucher_information'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('admin/voucher-resource.fields.name'))
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label(__('admin/voucher-resource.fields.description'))
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Group::make([
                                Select::make('voucher_type')
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
                                Select::make('discount_type')
                                    ->options([
                                        VoucherDiscountType::FIXED->value => __('admin/voucher-resource.fields.fixed'),
                                        VoucherDiscountType::PERCENTAGE->value => __('admin/voucher-resource.fields.percentage'),
                                    ])
                                    ->default(VoucherDiscountType::FIXED->value)
                                    ->required()
                                    ->live(),
                                Select::make('product_type')
                                    ->options([
                                        VoucherProductType::ALL_PRODUCT->value => __('admin/voucher-resource.fields.all_product'),
                                        VoucherProductType::PHYSICAL_PRODUCT->value => __('admin/voucher-resource.fields.physical_product'),
                                        VoucherProductType::DIGITAL_PRODUCT->value => __('admin/voucher-resource.fields.digital_product'),
                                    ])
                                    ->default(VoucherProductType::ALL_PRODUCT->value)
                                    ->disabled(fn(Get $get) => $get('voucher_type') == VoucherType::SHIPPING_COST->value)
                                    ->required(),
                            ])->columns(3),
                            TextInput::make('code')
                                ->label(__('admin/voucher-resource.fields.code'))
                                ->required()
                                ->maxLength(10),
                            Group::make([
                                TextInput::make('discount_min')
                                    ->label(__('admin/voucher-resource.fields.discount_min'))
                                    ->required(),

                                TextInput::make('discount')
                                    ->label(fn(Get $get) => $get('discount_type') == VoucherDiscountType::FIXED->value ? __('admin/voucher-resource.fields.discount') : __('admin/voucher-resource.fields.discount_percentage'))
                                    ->required()
                                    ->rules('numeric')
                                    ->maxLength(fn(Get $get) => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value ? 100 : null),
                                TextInput::make('discount_max')
                                    ->label(__('admin/voucher-resource.fields.discount_max'))
                                    ->required(fn(Get $get): bool => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value)
                                    ->visible(fn(Get $get): bool => $get('discount_type') == VoucherDiscountType::PERCENTAGE->value)
                            ])->columns(3)
                        ]),

                ])->columnSpan(2),
                Group::make([
                    Section::make(__('admin/voucher-resource.sections.validity_period'))
                        ->schema([
                            DatePicker::make('start_at')
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
                            DatePicker::make('end_at')
                                ->label(__('admin/voucher-resource.fields.end_at'))
                                ->required()
                                ->native(false)
                                ->minDate(fn(Get $get): ?string => $get('start_at')),
                        ]),
                    Section::make(__('admin/voucher-resource.sections.other_information'))
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
                            Select::make('category_id')
                                ->label(__('admin/voucher-resource.fields.category_id'))
                                ->placeholder(__('admin/voucher-resource.fields.all_category'))
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload(),
                            Toggle::make('is_public')
                                ->label(__('admin/voucher-resource.fields.is_public'))
                                ->default(true)
                                ->required(),
                            Toggle::make('is_active')
                                ->label(__('admin/voucher-resource.fields.is_active'))
                                ->default(true)
                                ->required(),
                            TextInput::make('max_user_used')
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
                TextColumn::make('name')
                    ->label(__('admin/voucher-resource.columns.name'))
                    ->icon(fn(Voucher $record): string => $record->is_public ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->iconColor(fn(Voucher $record): string => $record->is_public ? 'success' : 'warning')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('admin/voucher-resource.columns.code'))
                    ->weight('bold')
                    ->color('info')
                    ->searchable(),
                TextColumn::make('voucher_type')
                    ->badge(),
                TextColumn::make('discount_type')
                    ->badge(),
                TextColumn::make('product_type')
                    ->badge(),
                TextColumn::make('discount')
                    ->money(currency: 'IDR')
                    ->badge()
                    ->icon(fn(Voucher $record): string => match ($record->discount_type) {
                        VoucherDiscountType::FIXED => 'heroicon-o-tag',
                        VoucherDiscountType::PERCENTAGE => 'heroicon-o-receipt-percent',
                    })
                    ->color(fn(Voucher $record): string => $record->discount_type->value == VoucherDiscountType::FIXED->value ? 'success' : 'warning')
                    ->sortable(),
                TextColumn::make('validity_period')
                    ->sortable(
                        query: fn(string $direction, $query) => $query->orderBy('start_at', $direction)
                    ),
                self::getIsActiveColumn(),
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
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
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
            'index' => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit' => EditVoucher::route('/{record}/edit'),
        ];
    }

    public static function getIsActiveColumn()
    {
        if (self::shouldCanUpdate()) {
            return ToggleColumn::make('is_active')
                ->afterStateUpdated(fn() => notification(__('admin/voucher-resource.notifications.activation_updated'), 'success'))
                ->label(__('admin/voucher-resource.columns.active'));
        }

        return IconColumn::make('is_active')->boolean()->label(__('admin/voucher-resource.columns.active'));
    }

    public static function shouldCanUpdate(): bool
    {
        return auth()->user()->can('update_voucher');
    }
}
