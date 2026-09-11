<?php

namespace App\Filament\Resources\Customers;

use App\Constants\UploadPath;
use App\Filament\Clusters\CustomerCluster;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\Profile;
use App\Filament\Resources\Customers\RelationManagers\BalancesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\CustomerAddressRelationManager;
use App\Models\Customer;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static ?string $cluster = CustomerCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $slug = 'customers';

    protected static ?int $navigationSort = 0;

    public Customer $record;

    public static function getNavigationLabel(): string
    {
        return __('admin/customer-resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin/customer-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/customer-resource.plural_model_label');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'username', 'phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('admin/customer-resource.fields.email') => $record->email,
            __('admin/customer-resource.fields.phone') => $record->phone,
        ], fn (?string $value): bool => filled($value));
    }

    public static function shouldShowCreditInformation(): bool
    {
        return settings('enforce_credit_limit', true);
    }

    public static function shouldShowBalanceInformation(): bool
    {
        return settings('balance_enabled', true);
    }

    // protected static ?string $navigationLabel = 'Customer';
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin/customer-resource.sections.general_information'))
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('admin/customer-resource.fields.profile_image'))
                            ->image()
                            ->avatar()
                            ->directory(UploadPath::PROFILE_UPLOAD_PATH)
                            ->imageCropAspectRatio('1:1')
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                            ->disk(config('filesystems.upload_disk'))
                            ->columnSpanFull()
                            ->alignCenter()
                            ->helperText(__('admin/customer-resource.fields.profile_image_helper')),
                        TextInput::make('first_name')
                            ->label(__('admin/customer-resource.fields.first_name'))
                            ->placeholder(__('e.g: ').'John')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('last_name')
                            ->label(__('admin/customer-resource.fields.last_name'))
                            ->placeholder(__('e.g: ').'Smith')
                            ->maxLength(100),
                        TextInput::make('email')
                            ->label(__('admin/customer-resource.fields.email'))
                            ->placeholder(__('e.g: ').'Johnsmith@example.com')
                            ->email()
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label(__('admin/customer-resource.fields.phone'))
                            ->placeholder(__('e.g: ').'+6281234567890')
                            ->tel()
                            ->maxLength(20),
                        Select::make('customer_level_id')
                            ->label(__('admin/customer-resource.fields.customer_level_id'))
                            ->relationship('customerLevel', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('school_unit_id')
                            ->label(__('admin/customer-resource.fields.school_unit_id'))
                            ->relationship('schoolUnit', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Section::make(__('admin/customer-resource.sections.password'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('admin/customer-resource.fields.password'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rules([securePassword()])
                            ->required()
                            ->same('confirm_password')
                            ->minLength(8)
                            ->maxLength(20)
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        TextInput::make('confirm_password')
                            ->label(__('admin/customer-resource.fields.confirm_password'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->maxLength(255)
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                    ])
                    ->columns(2)
                    ->visible(fn (string $operation): bool => $operation === 'create'),

                Section::make(__('admin/customer-resource.sections.credit_settings'))
                    ->schema([
                        TextInput::make('credit_limit')
                            ->label(__('admin/customer-resource.fields.credit_limit'))
                            ->placeholder('Kosongkan untuk gunakan default dari level')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp'),
                        Placeholder::make('effective_credit_limit')
                            ->label(__('admin/customer-resource.fields.effective_credit_limit'))
                            ->content(fn ($record): string => 'Rp '.number_format($record?->effective_credit_limit ?? 0, 0, ',', '.')),
                        Placeholder::make('outstanding_balance')
                            ->label(__('admin/customer-resource.fields.outstanding_balance'))
                            ->content(fn ($record): string => 'Rp '.number_format($record?->outstanding_balance ?? 0, 0, ',', '.')),
                        Placeholder::make('remaining_credit_limit')
                            ->label(__('admin/customer-resource.fields.remaining_credit_limit'))
                            ->content(fn ($record): string => 'Rp '.number_format($record?->remaining_credit_limit ?? 0, 0, ',', '.')),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn (): bool => static::shouldShowCreditInformation()),

                Hidden::make('email_verified_at')
                    ->default(now())->dehydrated(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withTrashed())
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label(__('admin/customer-resource.columns.photo'))
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'border border-gray-200',
                        'lazy' => 'loading',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('full_name')
                    ->label(__('admin/customer-resource.columns.name'))
                    ->description(fn (Customer $record): string => $record->email)
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary'),
                TextColumn::make('email')
                    ->label(__('admin/customer-resource.columns.email'))
                    ->icon(fn ($record): string => $record?->email_verified_at ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconColor(fn ($record): string => $record?->email_verified_at ? 'success' : 'warning')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(__('admin/customer-resource.columns.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin/customer-resource.columns.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('customerLevel.name')
                    ->label(__('admin/customer-resource.columns.level'))
                    ->default('-')
                    ->badge()
                    ->color('info'),
                TextColumn::make('schoolUnit.name')
                    ->label(__('admin/customer-resource.columns.school_unit'))
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                TextColumn::make('balance')
                    ->label(fn (): string => static::shouldShowBalanceInformation() && static::shouldShowCreditInformation()
                        ? __('admin/customer-resource.columns.balance_and_credit')
                        : (static::shouldShowBalanceInformation()
                            ? __('admin/customer-resource.columns.balance')
                            : __('admin/customer-resource.columns.remaining_credit_limit')))
                    ->getStateUsing(fn (Customer $record): float => static::shouldShowBalanceInformation()
                        ? (float) $record->balance
                        : (float) $record->remaining_credit_limit)
                    ->money('IDR')
                    ->description(fn (Customer $record): ?string => static::shouldShowBalanceInformation() && static::shouldShowCreditInformation()
                        ? __('admin/customer-resource.columns.remaining_credit_limit').': Rp '.number_format($record->remaining_credit_limit, 0, ',', '.')
                        : null)
                    ->color(fn (Customer $record): string => static::shouldShowBalanceInformation()
                        ? ((float) $record->balance > 0 ? 'success' : 'gray')
                        : ($record->remaining_credit_limit > 0 ? 'success' : 'danger'))
                    ->visible(fn (): bool => static::shouldShowBalanceInformation() || static::shouldShowCreditInformation()),
                TextColumn::make('effective_credit_limit')
                    ->label(__('admin/customer-resource.columns.credit_limit'))
                    ->money('IDR')
                    ->sortable()
                    ->visible(fn (): bool => static::shouldShowCreditInformation())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin/customer-resource.columns.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label(__('admin/customer-resource.filters.email_verification'))
                    ->nullable()
                    ->placeholder(__('admin/customer-resource.filters.all'))
                    ->trueLabel(__('admin/customer-resource.filters.verified'))
                    ->falseLabel(__('admin/customer-resource.filters.not_verified')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('verifed_email')
                        ->label(__('admin/customer-resource.actions.verification_email'))
                        ->icon('heroicon-o-at-symbol')
                        ->requiresConfirmation()
                        ->action(function (Customer $record) {
                            $record->update(['email_verified_at' => now()]);

                            return notification(__('admin/customer-resource.notifications.verification_email_sent'));
                        })
                        ->color('danger')
                        ->visible(fn (Customer $record): bool => $record->email_verified_at === null),
                    ViewAction::make()->label(__('admin/customer-resource.actions.profile'))
                        ->color('info'),
                    EditAction::make()->label(__('admin/customer-resource.actions.edit')),
                    RestoreAction::make(),
                ])->tooltip(__('admin/customer-resource.actions.edit')),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin/customer-resource.actions.delete')),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return static::shouldShowBalanceInformation()
            ? [CustomerAddressRelationManager::class, BalancesRelationManager::class]
            : [CustomerAddressRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => Profile::route('/{record}/profile'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
