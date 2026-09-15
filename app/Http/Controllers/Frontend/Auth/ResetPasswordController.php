<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ResetPasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->input('email', ''),
            'guard' => (string) $request->input('guard', 'customer'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', securePassword(8)],
            'guard' => ['sometimes', 'string', 'in:customer,user'],
        ]);
        $guard = (string) ($validated['guard'] ?? 'customer');
        $status = $this->passwordResetService->reset($guard, [
            'token' => (string) $validated['token'],
            'email' => (string) $validated['email'],
            'password' => (string) $validated['password'],
            'password_confirmation' => (string) ($validated['password_confirmation'] ?? ''),
        ]);

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route($guard === 'customer' ? 'frontend.home' : 'home')->with('status', trans($status));
        }

        throw ValidationException::withMessages(['email' => [trans($status)]]);
    }
}
