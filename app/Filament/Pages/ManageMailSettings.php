<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\SettingsCluster;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;

class ManageMailSettings extends SettingsPage
{
    use HasPageShield;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'mail';

    protected static string $settings = GeneralSettings::class;

    public static function getNavigationLabel(): string
    {
        return __('admin/page-manage-mail.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin/page-manage-mail.title');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['mail_password'] = null;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            Section::make(__('admin/page-manage-mail.sections.connection'))
                ->description(__('admin/page-manage-mail.descriptions.connection'))
                ->columnSpanFull()
                ->schema([
                        TextInput::make('mail_from')
                            ->label(__('admin/page-manage-mail.fields.mail_from'))
                            ->email()
                            ->required(),
                        TextInput::make('mail_host')
                            ->label(__('admin/page-manage-mail.fields.mail_host'))
                            ->required(),
                        TextInput::make('mail_port')
                            ->label(__('admin/page-manage-mail.fields.mail_port'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535)
                            ->required(),
                        Select::make('mail_encryption')
                            ->label(__('admin/page-manage-mail.fields.mail_encryption'))
                            ->options([
                                'ssl' => 'SSL',
                                'tls' => 'TLS',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            Section::make(__('admin/page-manage-mail.sections.credentials'))
                ->description(__('admin/page-manage-mail.descriptions.credentials'))
                ->columnSpanFull()
                ->schema([
                        TextInput::make('mail_username')
                            ->label(__('admin/page-manage-mail.fields.mail_username'))
                            ->email()
                            ->required(),
                        TextInput::make('mail_password')
                            ->label(__('admin/page-manage-mail.fields.mail_password'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->helperText(__('admin/page-manage-mail.fields.mail_password_helper'))
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            Section::make(__('admin/page-manage-mail.sections.test_email'))
                ->columnSpanFull()
                ->schema([
                        Actions::make([
                            Action::make('test_email')
                                ->label(__('admin/page-manage-mail.actions.test_email'))
                                ->icon('heroicon-o-paper-airplane')
                                ->form([
                                    TextInput::make('recipient_email')
                                        ->label(__('admin/page-manage-mail.actions.recipient_email'))
                                        ->email()
                                        ->required(),
                                ])
                                ->modalSubmitActionLabel(__('admin/page-manage-mail.actions.modal_submit'))
                                ->modalCancelActionLabel(__('admin/page-manage-mail.actions.modal_cancel'))
                                ->action(function (array $data): void {
                                    $settings = app(GeneralSettings::class);
                                    $formData = $this->data;

                                    $settings->loadMailSettingsToConfig([
                                        'mail_host' => $formData['mail_host'] ?? null,
                                        'mail_port' => $formData['mail_port'] ?? null,
                                        'encryption' => $formData['mail_encryption'] ?? null,
                                        'username' => $formData['mail_username'] ?? null,
                                        'password' => $formData['mail_password'] ?? null,
                                        'from_address' => $formData['mail_from'] ?? null,
                                        'from_name' => $formData['mail_from'] ?? null,
                                    ]);

                                    try {
                                        Mail::raw(__('admin/page-manage-mail.messages.test_email_body'), function ($message) use ($data): void {
                                            $message
                                                ->to($data['recipient_email'])
                                                ->subject(__('admin/page-manage-mail.messages.test_email_subject'));
                                        });

                                        Notification::make()
                                            ->title(__('admin/page-manage-mail.notifications.test_email_success'))
                                            ->success()
                                            ->send();
                                    } catch (\Exception $exception) {
                                        Notification::make()
                                            ->title(__('admin/page-manage-mail.notifications.test_email_failed') . ': ' . $exception->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ]),
                    ]),
            ]);
    }
}
