<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
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

        $data['admin_emails'] = array_values(array_filter(
            array_map('trim', explode(',', (string) ($data['admin_emails'] ?? ''))),
        ));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $emails = array_filter(array_map(
            static fn (string $email): string => trim($email),
            $data['admin_emails'] ?? [],
        ));

        $data['admin_emails'] = implode(',', array_unique($emails));

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
                        Repeater::make('admin_emails')
                            ->label(__('admin/page-manage-notifications.fields.admin_emails'))
                            ->helperText(__('admin/page-manage-notifications.fields.admin_emails_helper'))
                            ->simple(
                                TextInput::make('email')
                                    ->email()
                                    ->required(),
                            )
                            ->defaultItems(0)
                            ->addActionLabel(__('admin/page-manage-notifications.fields.admin_emails_add'))
                            ->reorderable(false),
                    ]),
            ]);
    }
}
