<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Balance;
use App\Models\Customer;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Filament\Actions as Act;
use Livewire\Component;

class Profile extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function balance($data, $record, string $trx_type)
    {
        DB::beginTransaction();
        try {
            // Determine the new balance based on the transaction type
            if ($trx_type === '+') {
                $record->increment('balance', $data['balance']);
                $text = __('Balance added successfully');
            } else {
                $record->decrement('balance', $data['balance']);
                $text = __('Balance reduced successfully');
            }

            // Use the updateBalance function to generate the transaction data
            $data = (new Balance)->updateBalance(
                customer_id: $record->id,
                amount: $data['balance'],
                charge: 0,
                post_balance: $record->balance,
                trx_type: $trx_type,
                notes: $data['notes'] ?? null
            );

            // Create the balance record
            $record->balances()->create($data);

            DB::commit();
            return notification($text);
        } catch (\Exception $e) {
            DB::rollBack();
            return notification(__('Balance update failed'), 'danger');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Act\Action::make('change_password')
                ->label(__('admin/customer-resource.actions.change_password'))
                ->icon('heroicon-o-lock-closed')
                ->form([
                    TextInput::make('password')
                        ->password()
                        ->label(__('admin/customer-resource.actions.new_password'))
                        ->rules([securePassword()])
                        ->required()
                        ->minLength(8)
                        ->maxLength(20),
                    TextInput::make('password_confirmation')
                        ->password()
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
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
                        TextEntry::make('is_active')
                            ->label(__('admin/customer-resource.profile.status'))
                            ->badge()
                            ->color(fn($record): string => $record->is_active ? 'success' : 'danger')
                            ->formatStateUsing(fn($state): string => $state ? __('admin/customer-resource.profile.active') : __('admin/customer-resource.profile.inactive')),
                    ])->inlineLabel()->columnSpan(2),

                Section::make(__('admin/customer-resource.sections.credit_settings'))
                    ->icon('heroicon-o-star')
                    ->schema([
                        TextEntry::make('customerLevel.name')
                            ->label(__('admin/customer-resource.profile.customer_level'))
                            ->default('-'),
                        TextEntry::make('effective_credit_limit')
                            ->label(__('admin/customer-resource.profile.credit_limit'))
                            ->money('IDR'),
                        TextEntry::make('outstanding_balance')
                            ->label(__('admin/customer-resource.profile.outstanding'))
                            ->money('IDR'),
                        TextEntry::make('remaining_credit_limit')
                            ->label(__('admin/customer-resource.profile.remaining_credit'))
                            ->money('IDR'),
                    ])->inlineLabel()->columnSpan(2),



            ])->columns(3);
    }


    protected function getHeaderWidgets(): array
    {

        return [
            // TotalBalanceWidget::class,
        ];
    }
}
