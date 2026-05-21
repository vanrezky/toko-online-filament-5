<?php

namespace App\Filament\Resources;

use App\Constants\UploadPath;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\BalancesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\CustomerAddressRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $slug = 'customers';
    protected static ?int $navigationSort = 1;

    public Customer $record;

    public static function getNavigationLabel(): string
    {
        return __('admin/customer-resource.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/customer-resource.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('admin/customer-resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin/customer-resource.plural_model_label');
    }

    // protected static ?string $navigationLabel = 'Customer';
    // protected static ?string $recordTitleAttribute = 'first_name';
    // protected static int $globalSearchResultsLimit = 10;

    // public static  function getGlobalSearchResultTitle(Model $record): string
    // {
    //     return $record->first_name . ' ' .  $record->last_name;
    // }

    // public static function getGloballySearchableAttributes(): array
    // {
    //     return ['first_name', 'last_name', 'departement.name'];
    // }

    // public static function getGlobalSearchResultDetails(Model $record): array
    // {
    //     return [
    //         'Country' => $record->country->name,
    //         'Departement' => $record->departement->name
    //     ];
    // }

    // public static function getGlobalSearchEloquentQuery(): Builder
    // {
    //     return parent::getGlobalSearchEloquentQuery()->with(['departement', 'country']);
    // }

    // public static function getGlobalSearchResultUrl(Model $record): string
    // {
    //     return EmployeeResource::getUrl('view', ['record' => $record]);
    // }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Services\NavigationBadgeCache::getCustomerCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return \App\Services\NavigationBadgeCache::getCustomerCount() > 10 ? 'info' : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin/customer-resource.sections.general_information'))
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label(__('admin/customer-resource.fields.profile_image'))
                            ->image()
                            ->avatar()
                            ->directory(UploadPath::PROFILE_UPLOAD_PATH)
                            ->imageCropAspectRatio('1:1')
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                            ->columnSpanFull()
                            ->alignCenter()
                            ->helperText(__('admin/customer-resource.fields.profile_image_helper')),
                        Forms\Components\TextInput::make('first_name')
                            ->label(__('admin/customer-resource.fields.first_name'))
                            ->placeholder(__('e.g: ') . 'John')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('last_name')
                            ->label(__('admin/customer-resource.fields.last_name'))
                            ->placeholder(__('e.g: ') . 'Smith')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->label(__('admin/customer-resource.fields.email'))
                            ->placeholder(__('e.g: ') . 'Johnsmith@example.com')
                            ->email()
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('admin/customer-resource.fields.phone'))
                            ->placeholder(__('e.g: ') . '+6281234567890')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('customer_level_id')
                            ->label(__('admin/customer-resource.fields.customer_level_id'))
                            ->relationship('customerLevel', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('school_unit_id')
                            ->label(__('admin/customer-resource.fields.school_unit_id'))
                            ->relationship('schoolUnit', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('admin/customer-resource.sections.password'))
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label(__('admin/customer-resource.fields.password'))
                            ->password()
                            ->rules([securePassword()])
                            ->required()
                            ->same('confirm_password')
                            ->minLength(8)
                            ->maxLength(20)
                            ->visible(fn(string $operation): bool  => $operation === 'create'),
                        Forms\Components\TextInput::make('confirm_password')
                            ->label(__('admin/customer-resource.fields.confirm_password'))
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->visible(fn(string $operation): bool  => $operation === 'create'),
                    ])
                    ->columns(2)
                    ->visible(fn(string $operation): bool  => $operation === 'create'),

                Forms\Components\Section::make(__('admin/customer-resource.sections.credit_settings'))
                    ->schema([
                        Forms\Components\TextInput::make('credit_limit')
                            ->label(__('admin/customer-resource.fields.credit_limit'))
                            ->placeholder('Kosongkan untuk gunakan default dari level')
                            ->numeric()
                            ->nullable()
                            ->prefix('Rp')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        Forms\Components\Placeholder::make('effective_credit_limit')
                            ->label(__('admin/customer-resource.fields.effective_credit_limit'))
                            ->content(fn($record): string => 'Rp ' . number_format($record?->effective_credit_limit ?? 0, 0, ',', '.')),
                        Forms\Components\Placeholder::make('outstanding_balance')
                            ->label(__('admin/customer-resource.fields.outstanding_balance'))
                            ->content(fn($record): string => 'Rp ' . number_format($record?->outstanding_balance ?? 0, 0, ',', '.')),
                        Forms\Components\Placeholder::make('remaining_credit_limit')
                            ->label(__('admin/customer-resource.fields.remaining_credit_limit'))
                            ->content(fn($record): string => 'Rp ' . number_format($record?->remaining_credit_limit ?? 0, 0, ',', '.')),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Hidden::make('email_verified_at')
                    ->default(now())->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->withTrashed())
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo_url')
                    ->label(__('admin/customer-resource.columns.photo'))
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'border border-gray-200',
                        'lazy' => 'loading'
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('admin/customer-resource.columns.name'))
                    ->description(fn (Customer $record): string => $record->email)
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary'),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin/customer-resource.columns.email'))
                    ->icon(fn($record): string => $record?->email_verified_at ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->iconColor(fn($record): string => $record?->email_verified_at ? 'success' : 'warning')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin/customer-resource.columns.phone'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin/customer-resource.columns.active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customerLevel.name')
                    ->label(__('admin/customer-resource.columns.level'))
                    ->default('-')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('schoolUnit.name')
                    ->label(__('admin/customer-resource.columns.school_unit'))
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('remaining_credit_limit')
                    ->label(__('admin/customer-resource.columns.remaining_credit_limit'))
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (Customer $record): string => $record->remaining_credit_limit > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('effective_credit_limit')
                    ->label(__('admin/customer-resource.columns.credit_limit'))
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin/customer-resource.columns.created_at'))
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('verifed_email')
                        ->label(__('admin/customer-resource.actions.verification_email'))
                        ->icon('heroicon-o-at-symbol')
                        ->requiresConfirmation()
                        ->action(function (Customer $record) {
                            $record->update(['email_verified_at' => now()]);
                            return notification(__('admin/customer-resource.notifications.verification_email_sent'));
                        })
                        ->color('danger')
                        ->visible(fn(Customer $record): bool => $record->email_verified_at === null),
                    Tables\Actions\ViewAction::make()->label(__('admin/customer-resource.actions.profile'))
                        ->color('info'),
                    Tables\Actions\EditAction::make()->label(__('admin/customer-resource.actions.edit')),
                    Tables\Actions\RestoreAction::make(),
                ])->tooltip(__('admin/customer-resource.actions.edit'))

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('admin/customer-resource.actions.delete')),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CustomerAddressRelationManager::class,
            // BalancesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\Profile::route('/{record}/profile'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
