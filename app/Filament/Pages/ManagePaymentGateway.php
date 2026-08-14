<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\Currency;
use App\Settings\PaymentGatewaySettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class ManagePaymentGateway extends Page
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $cluster = SettingsCluster::class;
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'payment-gateway';
    protected string $view = 'filament.pages.manage-payment-gateway';


    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('admin/page-manage-payment-gateway.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin/page-manage-payment-gateway.title');
    }

    public function mount(PaymentGatewaySettings $settings): void
    {
        $data = $settings->toArray();
        $gatewayAliases = ['midtrans', 'stripe', 'xendit'];

        foreach ($gatewayAliases as $alias) {
            $data["{$alias}_is_active"] = ($settings->active_gateway === $alias);
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $currencies = Currency::active()->pluck('name', 'code')->toArray();
        $gatewayAliases = ['midtrans', 'stripe', 'xendit'];

        return $schema
            ->components([
                Tabs::make('Payment Gateways')
                    ->tabs([
                        Tab::make('midtrans')
                            ->label(__('admin/page-manage-payment-gateway.tabs.midtrans'))
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Toggle::make('midtrans_is_active')
                                    ->label(__('admin/page-manage-payment-gateway.fields.set_active'))
                                    ->helperText(__('admin/page-manage-payment-gateway.fields.set_active_helper'))
                                    ->afterStateUpdated(function ($state, callable $set) use ($gatewayAliases) {
                                        if ($state) {
                                            foreach ($gatewayAliases as $alias) {
                                                if ($alias !== 'midtrans') {
                                                    $set("{$alias}_is_active", false);
                                                }
                                            }
                                        }
                                    }),

                                Section::make(__('admin/page-manage-payment-gateway.sections.credentials'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('midtrans_server_key')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.server_key'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable())
                                                    ->helperText(new HtmlString(__('admin/page-manage-payment-gateway.fields.server_key_helper'))),

                                                TextInput::make('midtrans_client_key')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.client_key'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable()),
                                            ]),

                                        TextInput::make('midtrans_merchant_id')
                                            ->label(__('admin/page-manage-payment-gateway.fields.merchant_id'))
                                            ->placeholder('MCH-XXXXXX'),
                                    ]),

                                Section::make(__('admin/page-manage-payment-gateway.sections.configuration'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('midtrans_mode')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.mode'))
                                                    ->options([
                                                        'sandbox' => __('admin/page-manage-payment-gateway.fields.sandbox'),
                                                        'production' => __('admin/page-manage-payment-gateway.fields.production'),
                                                    ])
                                                    ->default('sandbox'),

                                                Select::make('midtrans_supported_currencies')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.supported_currencies'))
                                                    ->options($currencies)
                                                    ->multiple()
                                                    ->preload(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('stripe')
                            ->label(__('admin/page-manage-payment-gateway.tabs.stripe'))
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Toggle::make('stripe_is_active')
                                    ->label(__('admin/page-manage-payment-gateway.fields.set_active'))
                                    ->helperText(__('admin/page-manage-payment-gateway.fields.set_active_helper'))
                                    ->afterStateUpdated(function ($state, callable $set) use ($gatewayAliases) {
                                        if ($state) {
                                            foreach ($gatewayAliases as $alias) {
                                                if ($alias !== 'stripe') {
                                                    $set("{$alias}_is_active", false);
                                                }
                                            }
                                        }
                                    }),

                                Section::make(__('admin/page-manage-payment-gateway.sections.credentials'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('stripe_api_key')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.api_key'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable())
                                                    ->helperText(new HtmlString(__('admin/page-manage-payment-gateway.fields.api_key_helper'))),

                                                TextInput::make('stripe_webhook_secret')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.webhook_secret'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable())
                                                    ->helperText(__('admin/page-manage-payment-gateway.fields.webhook_secret_helper')),
                                            ]),
                                    ]),

                                Section::make(__('admin/page-manage-payment-gateway.sections.configuration'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('stripe_mode')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.mode'))
                                                    ->options([
                                                        'test' => __('admin/page-manage-payment-gateway.fields.test'),
                                                        'live' => __('admin/page-manage-payment-gateway.fields.live'),
                                                    ])
                                                    ->default('test'),

                                                Select::make('stripe_supported_currencies')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.supported_currencies'))
                                                    ->options($currencies)
                                                    ->multiple()
                                                    ->preload(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('xendit')
                            ->label(__('admin/page-manage-payment-gateway.tabs.xendit'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Toggle::make('xendit_is_active')
                                    ->label(__('admin/page-manage-payment-gateway.fields.set_active'))
                                    ->helperText(__('admin/page-manage-payment-gateway.fields.set_active_helper'))
                                    ->afterStateUpdated(function ($state, callable $set) use ($gatewayAliases) {
                                        if ($state) {
                                            foreach ($gatewayAliases as $alias) {
                                                if ($alias !== 'xendit') {
                                                    $set("{$alias}_is_active", false);
                                                }
                                            }
                                        }
                                    }),

                                Section::make(__('admin/page-manage-payment-gateway.sections.credentials'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('xendit_api_key')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.api_key'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable())
                                                    ->helperText(new HtmlString(__('admin/page-manage-payment-gateway.fields.xendit_api_key_helper'))),

                                                TextInput::make('xendit_secret_key')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.secret_key'))
                                                    ->password()
                                                    ->revealable(filament()->arePasswordsRevealable()),
                                            ]),
                                    ]),

                                Section::make(__('admin/page-manage-payment-gateway.sections.configuration'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('xendit_mode')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.mode'))
                                                    ->options([
                                                        'test' => __('admin/page-manage-payment-gateway.fields.test'),
                                                        'live' => __('admin/page-manage-payment-gateway.fields.live'),
                                                    ])
                                                    ->default('test'),

                                                Select::make('xendit_supported_currencies')
                                                    ->label(__('admin/page-manage-payment-gateway.fields.supported_currencies'))
                                                    ->options($currencies)
                                                    ->multiple()
                                                    ->preload(),
                                            ]),
                                    ]),
                            ]),
                    ])->columnSpanFull(),

                Section::make(__('admin/page-manage-payment-gateway.sections.default_settings'))
                    ->schema([
                        Select::make('default_currency')
                            ->label(__('admin/page-manage-payment-gateway.fields.default_currency'))
                            ->options($currencies)
                            ->searchable()
                            ->preload(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(PaymentGatewaySettings $settings): void
    {
        $data = $this->form->getState();

        $activeGateway = null;
        $gatewayAliases = ['midtrans', 'stripe', 'xendit'];

        foreach ($gatewayAliases as $alias) {
            $isActiveKey = "{$alias}_is_active";
            if (isset($data[$isActiveKey]) && $data[$isActiveKey]) {
                $activeGateway = $alias;
                break;
            }
        }

        $settings->active_gateway = $activeGateway;

        $gatewayFields = [
            'midtrans' => ['server_key', 'client_key', 'merchant_id', 'mode', 'supported_currencies'],
            'stripe' => ['api_key', 'webhook_secret', 'mode', 'supported_currencies'],
            'xendit' => ['api_key', 'secret_key', 'mode', 'supported_currencies'],
        ];

        foreach ($gatewayFields as $gateway => $fields) {
            foreach ($fields as $field) {
                $key = "{$gateway}_{$field}";
                if (array_key_exists($key, $data)) {
                    $settings->{$key} = $data[$key];
                }
            }
        }

        if (isset($data['default_currency'])) {
            $settings->default_currency = $data['default_currency'];
        }

        $settings->save();

        Notification::make()
            ->title(__('admin/page-manage-payment-gateway.notifications.saved'))
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        return (bool) $user?->is_super_user || (bool) $user?->can('View:ManagePaymentGateway');
    }
}
