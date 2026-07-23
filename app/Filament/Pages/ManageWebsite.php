<?php

namespace App\Filament\Pages;

use App\Constants\UploadPath;
use App\Filament\Clusters\SettingsCluster;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ManageWebsite extends SettingsPage
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'website';

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-manage-website.navigation_label');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset(
            $data['mail_from'],
            $data['mail_host'],
            $data['mail_port'],
            $data['mail_encryption'],
            $data['mail_username'],
            $data['mail_password'],
        );

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('website-settings')
                    ->tabs([
                        Tab::make(__('admin/page-manage-website.tabs.storefront'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make(__('admin/page-manage-website.sections.identity'))
                                    ->schema([
                                        TextInput::make('site_name')
                                            ->label(__('admin/page-manage-website.fields.site_name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('site_tag_line')
                                            ->label(__('admin/page-manage-website.fields.site_tag_line'))
                                            ->maxLength(255),
                                        TextInput::make('address')
                                            ->label(__('admin/page-manage-website.fields.address'))
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make(__('admin/page-manage-website.sections.currency'))
                                    ->schema([
                                        TextInput::make('currency_text')
                                            ->label(__('admin/page-manage-website.fields.currency_text'))
                                            ->required()
                                            ->maxLength(5),
                                        TextInput::make('currency_symbol')
                                            ->label(__('admin/page-manage-website.fields.currency_symbol'))
                                            ->required()
                                            ->maxLength(5),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make(__('admin/page-manage-website.tabs.branding'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Section::make(__('admin/page-manage-website.sections.brand_assets'))
                                    ->schema([
                                        FileUpload::make('logo')
                                            ->label(__('admin/page-manage-website.fields.logo'))
                                            ->image()
                                            ->maxSize(1024)
                                            ->rules(['nullable', 'mimes:png,jpg,jpeg,webp,avif', 'max:1024'])
                                            ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                            ->helperText(__('admin/page-manage-website.file_helpers.logo_supported'))
                                            ->imageEditor(),
                                        FileUpload::make('login_logo')
                                            ->label(__('admin/page-manage-website.fields.login_logo'))
                                            ->image()
                                            ->maxSize(1024)
                                            ->rules(['nullable', 'mimes:png,jpg,jpeg,webp,avif', 'max:1024'])
                                            ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                            ->helperText(__('admin/page-manage-website.file_helpers.logo_supported')),
                                        FileUpload::make('favicon')
                                            ->label(__('admin/page-manage-website.fields.favicon'))
                                            ->image()
                                            ->maxSize(1024)
                                            ->rules(['nullable', 'mimes:png,jpg,jpeg,webp,avif,ico', 'max:1024'])
                                            ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                            ->helperText(__('admin/page-manage-website.file_helpers.favicon_supported')),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make(__('admin/page-manage-website.tabs.discoverability'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Section::make(__('admin/page-manage-website.sections.seo_social'))
                                    ->schema([
                                        FileUpload::make('social_image')
                                            ->label(__('admin/page-manage-website.fields.social_image'))
                                            ->helperText(__('admin/page-manage-website.file_helpers.social_image'))
                                            ->rules(['nullable', 'mimes:png,jpg,jpeg,webp,avif', 'max:1024'])
                                            ->directory(UploadPath::IMAGES_UPLOAD_PATH)
                                            ->image()
                                            ->imageResizeTargetWidth('1180')
                                            ->imageResizeTargetHeight('600'),
                                        Textarea::make('site_description')
                                            ->label(__('admin/page-manage-website.fields.site_description'))
                                            ->maxLength(300),
                                        TextInput::make('site_keywords')
                                            ->label(__('admin/page-manage-website.fields.site_keywords'))
                                            ->helperText(__('admin/page-manage-website.fields.site_keywords_helper')),
                                        TextInput::make('social_title')
                                            ->label(__('admin/page-manage-website.fields.social_title')),
                                        TextInput::make('social_description')
                                            ->label(__('admin/page-manage-website.fields.social_description')),
                                    ])
                                    ->columns(2),
                                Section::make(__('admin/page-manage-website.sections.contact_social'))
                                    ->schema([
                                        TextInput::make('phone')->label(__('admin/page-manage-website.fields.phone'))->tel(),
                                        TextInput::make('wa_phone')->label(__('admin/page-manage-website.fields.wa_phone'))->tel(),
                                        TextInput::make('instagram')->label(__('admin/page-manage-website.fields.instagram'))->url()->maxLength(255),
                                        TextInput::make('facebook')->label(__('admin/page-manage-website.fields.facebook'))->url()->maxLength(255),
                                        TextInput::make('twitter')->label(__('admin/page-manage-website.fields.twitter'))->url()->maxLength(255),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make(__('admin/page-manage-website.tabs.access'))
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make(__('admin/page-manage-website.sections.customer_access'))
                                    ->schema([
                                        Toggle::make('registration')
                                            ->label(__('admin/page-manage-website.fields.registration'))
                                            ->helperText(__('admin/page-manage-website.fields.registration_helper')),
                                        Toggle::make('is_private_store')
                                            ->label(__('admin/page-manage-website.fields.private_store'))
                                            ->helperText(__('admin/page-manage-website.fields.private_store_helper')),
                                        Toggle::make('term_agreement')
                                            ->label(__('admin/page-manage-website.fields.term_agreement'))
                                            ->helperText(__('admin/page-manage-website.fields.term_agreement_helper')),
                                    ]),
                                Section::make(__('admin/page-manage-website.sections.security_availability'))
                                    ->schema([
                                        Toggle::make('force_ssl')
                                            ->label(__('admin/page-manage-website.fields.force_ssl'))
                                            ->helperText(__('admin/page-manage-website.fields.force_ssl_helper')),
                                        Toggle::make('secure_password')
                                            ->label(__('admin/page-manage-website.fields.secure_password'))
                                            ->helperText(__('admin/page-manage-website.fields.secure_password_helper')),
                                        Toggle::make('site_active')
                                            ->label(__('admin/page-manage-website.fields.site_active'))
                                            ->helperText(__('admin/page-manage-website.fields.site_active_helper')),
                                        Select::make('active_template')
                                            ->label(__('admin/page-manage-website.fields.active_template'))
                                            ->options(['default' => 'Default'])
                                            ->helperText(__('admin/page-manage-website.fields.active_template_helper')),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
