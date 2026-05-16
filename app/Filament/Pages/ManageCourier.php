<?php

namespace App\Filament\Pages;

use App\Models\Courier;
use App\Settings\CourierSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class ManageCourier extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'setting/courier';

    protected static string $view = 'filament.pages.manage-courier';

    public ?array $data = [];

    public function mount(CourierSettings $settings): void
    {
        $this->form->fill($settings->toArray());
    }

    public static function canAccess(): bool
    {
        return isSuperUser();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin/page-manage-courier.navigation_group');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('')
                    ->tabs([
                        Tab::make(__('admin/page-manage-courier.tabs.rajaongkir'))
                            ->label(__('admin/page-manage-courier.tabs.rajaongkir'))
                            ->icon('heroicon-o-truck')
                            ->visible(false)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('rajaongkir_api_key')
                                            ->label(__('admin/page-manage-courier.fields.rajaongkir_api_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(new HtmlString(__('admin/page-manage-courier.links.rajaongkir_get_key'))),
                                        Select::make('rajaongkir_api_type')
                                            ->label(__('admin/page-manage-courier.fields.rajaongkir_api_type'))
                                            ->options([
                                                'free' => 'Free',
                                                'starter' => 'Starter',
                                                'basic' => 'Basic',
                                                'pro' => 'Pro',
                                            ])
                                            ->required(),
                                        TextInput::make('rajaongkir_base_url')
                                            ->label(__('admin/page-manage-courier.fields.rajaongkir_base_url'))
                                            ->placeholder('https://api.rajaongkir.com/starter'),
                                        TextInput::make('rajaongkir_api_key_pro')
                                            ->label(__('admin/page-manage-courier.fields.rajaongkir_api_key_pro'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(new HtmlString(__('admin/page-manage-courier.links.rajaongkir_get_key'))),
                                        TextInput::make('rajaongkir_base_url_pro')
                                            ->label(__('admin/page-manage-courier.fields.rajaongkir_base_url_pro'))
                                            ->placeholder('https://pro.rajaongkir.com/api'),
                                    ]),
                            ]),
                        Tab::make(__('admin/page-manage-courier.tabs.apicoid'))
                            ->label(__('admin/page-manage-courier.tabs.apicoid'))
                            ->icon('heroicon-o-truck')
                            ->visible(false)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('apicoid_api_key')
                                            ->label(__('admin/page-manage-courier.fields.apicoid_api_key'))
                                            ->password()
                                            ->revealable()
                                            ->helperText(new HtmlString(__('admin/page-manage-courier.links.apicoid_get_key'))),
                                        TextInput::make('apicoid_base_url')
                                            ->label(__('admin/page-manage-courier.fields.apicoid_base_url'))
                                            ->placeholder('https://api.co.id/'),
                                    ]),

                            ]),
                        Tab::make(__('admin/page-manage-courier.tabs.kurir_toko'))
                            ->label(__('admin/page-manage-courier.tabs.kurir_toko'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('kurir_toko_price')
                                            ->label(__('admin/page-manage-courier.fields.kurir_toko_price'))
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->required(),
                                    ]),
                            ]),
                    ]),

                Section::make(__('admin/page-manage-courier.fields.general'))
                    ->schema([
                        Select::make('default_courier')
                            ->label(__('admin/page-manage-courier.fields.default_courier'))
                            ->options(Courier::pluck('name', 'code'))
                            ->searchable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(CourierSettings $settings): void
    {
        $settings->fill($this->form->getState());
        $settings->save();

        Notification::make()
            ->title(__('admin/page-manage-courier.notifications.settings_saved'))
            ->success()
            ->send();
    }

    public function getCouriers(): Collection
    {
        return Courier::orderBy('name')->get();
    }

    public function toggleStatus(int $id): void
    {
        $courier = Courier::find($id);
        $courier->is_active = !$courier->is_active;
        $courier->save();

        Notification::make()
            ->title(__('admin/page-manage-courier.notifications.status_updated'))
            ->success()
            ->send();
    }
}
