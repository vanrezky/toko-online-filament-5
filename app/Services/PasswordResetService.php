<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerResetPasswordNotification;
use App\Repositories\CustomerAuthRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class PasswordResetService
{
    public function __construct(private readonly CustomerAuthRepository $authRepository) {}

    public function sendResetLink(string $guard, string $email): ?int
    {
        $user = $this->authRepository->findPasswordRecipient($guard, $email);
        if (! $user instanceof Customer && ! $user instanceof User) {
            return null;
        }

        $token = Password::broker($guard === 'customer' ? 'customers' : 'users')->createToken($user);
        $user->notify((new CustomerResetPasswordNotification($token, $guard))->onQueue('default'));

        return (int) $user->getKey();
    }

    /** @param array{token: string, email: string, password: string, password_confirmation: string} $credentials */
    public function reset(string $guard, array $credentials): string
    {
        return Password::broker($guard === 'customer' ? 'customers' : 'users')->reset(
            $credentials,
            function (Customer|User $user, string $password) use ($guard): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                Auth::guard($guard === 'customer' ? 'customer' : 'web')->login($user);
            },
        );
    }
}
