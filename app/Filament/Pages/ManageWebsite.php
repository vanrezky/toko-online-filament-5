<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Group;
use App\Constants\UploadPath;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Override;

class ManageWebsite extends SettingsPage
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'setting/settings';

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('admin/page-manage-website.navigation_group');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->schema([
                        Tab::make(__('admin/page-manage-website.tabs.general'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextInput::make('site_name')->label(__('admin/page-manage-website.fields.site_name'))->required()->maxLength(255),
                                        TextInput::make('site_tag_line')->label(__('admin/page-manage-website.fields.site_tag_line'))->maxLength(255),
                                        TextInput::make('address')->label(__('admin/page-manage-website.fields.address'))->maxLength(255),
                                        Group::make([
                                            TextInput::make('currency_text')->label(__('admin/page-manage-website.fields.currency_text'))->required()->maxLength(5),
                                            TextInput::make('currency_symbol')->label(__('admin/page-manage-website.fields.currency_symbol'))->required()->maxLength(5),
                                        ])->columns(2),

                                    ])->columnSpanFull(),
                            ])->columns(2),
                        Tab::make(__('admin/page-manage-website.tabs.logo'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('logo')
                                    ->label(__('admin/page-manage-website.fields.logo'))
                                    ->image()
                                    ->maxSize(1024)
                                    ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                                    ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                    ->helperText(__('admin/page-manage-website.file_helpers.logo_supported'))
                                    ->imageEditor(),
                                FileUpload::make('favicon')
                                    ->label(__('admin/page-manage-website.fields.favicon'))
                                    ->image()
                                    ->maxSize(1024)
                                    ->rules(['nullable', 'mimes:png,jpg,jpeg,ico', 'max:1024'])
                                    ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                    ->helperText(__('admin/page-manage-website.file_helpers.favicon_supported')),
                                FileUpload::make('login_logo')
                                    ->label(__('admin/page-manage-website.fields.login_logo'))
                                    ->image()
                                    ->maxSize(1024)
                                    ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                                    ->directory(UploadPath::CONFIG_UPLOAD_PATH)
                                    ->helperText(__('admin/page-manage-website.file_helpers.logo_supported')),
                            ])->columns(2),
                        Tab::make(__('admin/page-manage-website.tabs.seo'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                FileUpload::make('social_image')
                                    ->label(__('admin/page-manage-website.fields.social_image'))
                                    ->helperText(__('admin/page-manage-website.file_helpers.social_image'))
                                    ->rules(['nullable', 'mimes:png,jpg,jpeg', 'max:1024'])
                                    ->directory(UploadPath::IMAGES_UPLOAD_PATH)
                                    ->image()
                                    ->imageResizeMode('cover')
                                    ->imageResizeTargetWidth('1180')
                                    ->imageResizeTargetHeight('600'),
                                Group::make([
                                    Textarea::make('site_description')->label(__('admin/page-manage-website.fields.site_description'))->maxLength(300),
                                    TextInput::make('site_keywords')->label(__('admin/page-manage-website.fields.site_keywords'))->helperText(__('admin/page-manage-website.fields.site_keywords_helper')),
                                    TextInput::make('social_title')->label(__('admin/page-manage-website.fields.social_title')),
                                    TextInput::make('social_description')->label(__('admin/page-manage-website.fields.social_description')),
                                ])->columnSpan(2),
                            ])->columns(3),
                        Tab::make(__('admin/page-manage-website.tabs.contact_social_media'))
                            ->icon('heroicon-o-rss')
                            ->schema([
                                TextInput::make('phone')->label(__('admin/page-manage-website.fields.phone'))->tel(),
                                TextInput::make('wa_phone')->label(__('admin/page-manage-website.fields.wa_phone'))->tel(),
                                TextInput::make('instagram')->label(__('admin/page-manage-website.fields.instagram'))->url()->maxLength(255),
                                TextInput::make('facebook')->label(__('admin/page-manage-website.fields.facebook'))->url()->maxLength(255),
                                TextInput::make('twitter')->label(__('admin/page-manage-website.fields.twitter'))->url()->maxLength(255),

                            ]),

                        Tab::make(__('admin/page-manage-website.tabs.mail_config'))
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                TextInput::make('mail_from')->label(__('admin/page-manage-website.fields.mail_from')),
                                TextInput::make('mail_host')->label(__('admin/page-manage-website.fields.mail_host')),
                                TextInput::make('mail_port')->label(__('admin/page-manage-website.fields.mail_port'))->numeric()->maxLength(5),
                                Select::make('mail_encryption')
                                    ->label(__('admin/page-manage-website.fields.mail_encryption'))
                                    ->rules(['required', 'in:tls,ssl'])
                                    ->options([
                                        'ssl' => 'SSL',
                                        'tls' => 'TLS',
                                    ]),
                                TextInput::make('mail_username')->label(__('admin/page-manage-website.fields.mail_username'))->email(),
                                TextInput::make('mail_password')->label(__('admin/page-manage-website.fields.mail_password')),

                            ])->columns(2),
                        Tab::make(__('admin/page-manage-website.tabs.system'))
                            ->icon('heroicon-o-wrench')
                            ->schema([
                                Toggle::make('registration')
                                    ->label(__('admin/page-manage-website.fields.registration'))
                                    ->helperText(__('admin/page-manage-website.fields.registration_helper')),
                                Toggle::make('force_ssl')
                                    ->label(__('admin/page-manage-website.fields.force_ssl'))
                                    ->helperText(__('admin/page-manage-website.fields.force_ssl_helper')),
                                Toggle::make('secure_password')
                                    ->label(__('admin/page-manage-website.fields.secure_password'))
                                    ->helperText(__('admin/page-manage-website.fields.secure_password_helper')),
                                Toggle::make('term_agreement')
                                    ->label(__('admin/page-manage-website.fields.term_agreement'))
                                    ->helperText(__('admin/page-manage-website.fields.term_agreement_helper')),
                                Select::make('active_template')
                                    ->label(__('admin/page-manage-website.fields.active_template'))
                                    ->options([
                                        'default' => 'Default',
                                    ])
                                    ->helperText(__('admin/page-manage-website.fields.active_template_helper')),
                                Toggle::make('site_active')
                                    ->label(__('admin/page-manage-website.fields.site_active'))
                                    ->helperText(__('admin/page-manage-website.fields.site_active_helper')),
                            ]),
                        Tab::make('Pembayaran')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Toggle::make('enforce_credit_limit')
                                    ->label('Gunakan Limit Kredit')
                                    ->helperText('Nonaktifkan jika checkout anggota tidak lagi perlu dibatasi oleh sisa limit kredit.')
                                    ->default(true)
                                    ->required(),
                                TextInput::make('installment_min_order_amount')
                                    ->label('Minimum Belanja untuk Cicilan')
                                    ->helperText('Jika total belanja di bawah nilai ini, opsi cicilan akan dinonaktifkan.')
                                    ->rules('nullable|numeric')
                                    ->default(1000000)
                                    ->required(),
                                TextInput::make('billing_cutoff_day')
                                    ->label('Tanggal Cutoff Tagihan')
                                    ->helperText('Transaksi pada tanggal ini atau sebelumnya masuk siklus bulan berjalan.')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(25)
                                    ->required(),
                                TextInput::make('billing_due_day')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->helperText('Tanggal jatuh tempo payroll pada bulan penagihan.')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->default(5)
                                    ->required(),
                                TextInput::make('billing_due_month_offset')
                                    ->label('Offset Bulan Jatuh Tempo')
                                    ->helperText('0 = bulan yang sama, 1 = bulan berikutnya, dst.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(12)
                                    ->default(1)
                                    ->required(),
                            ]),
                        Tab::make(__('admin/page-manage-website.tabs.notifications'))
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Textarea::make('admin_emails')
                                    ->label(__('admin/page-manage-website.fields.admin_emails'))
                                    ->helperText(__('admin/page-manage-website.fields.admin_emails_helper'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])->columns(1),
                    ])->columnSpanFull(),
            ]);
    }
}
