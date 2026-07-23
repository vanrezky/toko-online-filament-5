<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Textarea;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageNotificationSettings extends SettingsPage
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'notifications';

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-manage-notifications.navigation_label');
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
            Section::make(__('admin/page-manage-notifications.sections.recipients'))
                ->description(__('admin/page-manage-notifications.descriptions.recipients'))
                ->columnSpanFull()
                ->schema([
                        Textarea::make('admin_emails')
                            ->label(__('admin/page-manage-notifications.fields.admin_emails'))
                            ->helperText(__('admin/page-manage-notifications.fields.admin_emails_helper'))
                            ->rows(3),
                    ]),
            ]);
    }
}
