<?php

namespace App\Filament\Resources\Customers\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Balances\BalanceResource;
use App\Models\Customer;
use App\Services\BalanceService;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions as Act;
use Filament\Support\Enums\Width;
use Livewire\Component;

class Profile extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    private function canManageBalance(): bool
    {
        return app(GeneralSettings::class)->balance_enabled
            && BalanceResource::canCreate();
    }

    protected function getHeaderActions(): array
    {
        return [
            Act\Action::make('top_up_balance')
                ->label(__('admin/customer-resource.actions.top_up_balance'))
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn (): bool => $this->canManageBalance())
                ->schema([
                    TextInput::make('amount')->numeric()->minValue(0.01)->required(),
                    TextInput::make('notes')->minLength(3)->maxLength(255)->required(),
                ])
                ->action(function (Customer $record, array $data): void {
                    app(BalanceService::class)->topUp($record, (float) $data['amount'], $data['notes'], auth()->user());
                    notification(__('admin/customer-resource.notifications.balance_added'));
                }),
            Act\Action::make('reduce_balance')
                ->label(__('admin/customer-resource.actions.reduce_balance'))
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->canManageBalance())
                ->schema([
                    TextInput::make('amount')->numeric()->minValue(0.01)->required(),
                    TextInput::make('notes')->minLength(3)->maxLength(255)->required(),
                ])
                ->action(function (Customer $record, array $data): void {
                    app(BalanceService::class)->reduce($record, (float) $data['amount'], $data['notes'], auth()->user());
                    notification(__('admin/customer-resource.notifications.balance_reduced'));
                }),
            Act\Action::make('change_password')
                ->label(__('admin/customer-resource.actions.change_password'))
                ->icon('heroicon-o-lock-closed')
                ->modalWidth(Width::Medium)
                ->modalSubmitActionLabel(__('labels.actions.save'))
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->label(__('admin/customer-resource.actions.new_password'))
                        ->rules([securePassword()])
                        ->required()
                        ->minLength(8)
                        ->maxLength(20),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->label(__('admin/customer-resource.actions.confirm_password'))
                        ->required()
                        ->maxLength(255)
                        ->same('password'),
                ])
                ->action(function (Customer $record, array $data) {
                    $record->update(['password' => $data['password']]);
                    return notification(__('admin/customer-resource.notifications.password_changed'));
                })
                ->color('warning'),
            Act\EditAction::make()->label(__('admin/customer-resource.actions.edit')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([

                Section::make([
                    ImageEntry::make('profile_photo_url')
                        ->hiddenLabel()
                        ->extraAttributes([
                            'class' => 'justify-center'
                        ])
                        ->extraImgAttributes([
                            'alt' => 'Profile photo',
                            'loading' => 'lazy',
                            'class' => 'border-2 border-primary'
                        ])
                        ->circular(),
                    TextEntry::make('full_name')
                        ->label(__('admin/customer-resource.profile.name'))
                        ->inlineLabel()
                        ->icon('heroicon-o-user')
                        ->iconColor('primary')
                        ->extraAttributes(['class' => 'font-bold -mb-8'])
                        ->alignEnd(),
                    TextEntry::make('email')
                        ->inlineLabel()
                        ->alignEnd()
                        ->icon('heroicon-o-envelope')
                        ->iconColor('primary'),
                    TextEntry::make('created_at')
                        ->label(__('admin/customer-resource.profile.member_since'))
                        ->inlineLabel()
                        ->dateTime()
                        ->alignEnd()
                        ->icon('heroicon-o-calendar')
                        ->iconColor('primary'),
                ])
                    ->columnSpan(1),

                Section::make(__('admin/customer-resource.sections.general_information'))
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextEntry::make('first_name')
                            ->label(__('admin/customer-resource.fields.first_name')),
                        TextEntry::make('last_name')
                            ->label(__('admin/customer-resource.fields.last_name')),
                        TextEntry::make('email')
                            ->label(__('admin/customer-resource.fields.email'))
                            ->icon(fn($record): string => match ($record->has_verified_email) {
                                true => 'heroicon-o-check-badge',
                                false => 'heroicon-o-x-circle',
                            })
                            ->iconColor(fn(Customer $record): string => $record->has_verified_email ? 'success' : 'danger')
                            ->iconPosition('after')
                            ->tooltip(fn(Customer $record): string => $record->has_verified_email ? __('admin/customer-resource.profile.email_verified') : __('admin/customer-resource.profile.email_unverified')),
                        TextEntry::make('phone')
                            ->label(__('admin/customer-resource.fields.phone')),
                        TextEntry::make('schoolUnit.name')
                            ->label(__('admin/customer-resource.fields.school_unit_id'))
                            ->default('-'),
                        TextEntry::make('customerLevel.name')
                            ->label(__('admin/customer-resource.profile.customer_level'))
                            ->default('-'),
                        TextEntry::make('is_active')
                            ->label(__('admin/customer-resource.profile.status'))
                            ->badge()
                            ->color(fn($record): string => $record->is_active ? 'success' : 'danger')
                            ->formatStateUsing(fn($state): string => $state ? __('admin/customer-resource.profile.active') : __('admin/customer-resource.profile.inactive')),
                    ])->inlineLabel()->columnSpan(2),

                Section::make(__('admin/customer-resource.sections.credit_and_balance'))
                    ->icon('heroicon-o-wallet')
                    ->schema([
                        TextEntry::make('effective_credit_limit')
                            ->label(__('admin/customer-resource.profile.credit_limit'))
                            ->money('IDR')
                            ->visible(fn (): bool => CustomerResource::shouldShowCreditInformation()),
                        TextEntry::make('outstanding_balance')
                            ->label(__('admin/customer-resource.profile.outstanding'))
                            ->money('IDR')
                            ->visible(fn (): bool => CustomerResource::shouldShowCreditInformation()),
                        TextEntry::make('remaining_credit_limit')
                            ->label(__('admin/customer-resource.profile.remaining_credit'))
                            ->money('IDR')
                            ->visible(fn (): bool => CustomerResource::shouldShowCreditInformation()),
                        TextEntry::make('balance')
                            ->label(__('admin/customer-resource.profile.balance'))
                            ->money('IDR')
                            ->icon('heroicon-o-wallet')
                            ->iconColor('success')
                            ->weight('bold')
                            ->visible(fn (): bool => CustomerResource::shouldShowBalanceInformation()),
                    ])
                    ->inlineLabel()
                    ->columnSpanFull()
                    ->visible(fn (): bool => CustomerResource::shouldShowCreditInformation() || CustomerResource::shouldShowBalanceInformation()),
            ])->columns(3);
    }


    protected function getHeaderWidgets(): array
    {

        return [
            // TotalBalanceWidget::class,
        ];
    }
}
