<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return __('admin/user-resource.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_password')
                ->label(__('admin/user-resource.actions.change_password'))
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->modalWidth(Width::Medium)
                ->modalSubmitActionLabel(__('labels.actions.save'))
                ->schema([
                    TextInput::make('password')
                        ->label(__('admin/user-resource.fields.new_password'))
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->rules([securePassword()])
                        ->required()
                        ->minLength(8)
                        ->maxLength(255),
                    TextInput::make('password_confirmation')
                        ->label(__('admin/user-resource.fields.confirm_password'))
                        ->password()
                        ->revealable(filament()->arePasswordsRevealable())
                        ->same('password')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (User $record, array $data): void {
                    $record->update(['password' => $data['password']]);

                    notification(__('admin/user-resource.notifications.password_changed'));
                }),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
