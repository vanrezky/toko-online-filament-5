<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    protected int $maxAttempts = 3;

    protected int $decayMinutes = 5;

    public function __invoke(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): Response
    {
        $request->validate([
            'email' => 'required|email',
            'guard' => 'sometimes|string|in:customer,user',
        ]);

        $guard = $request->input('guard', 'customer');

        if ($this->hasTooManyAttempts($request)) {
            Log::warning('Password reset throttled', [
                'email' => $request->email,
                'guard' => $guard,
                'ip' => $request->ip(),
            ]);
            $this->sendLockoutResponse($request);
        }

        $userId = $this->passwordResetService->sendResetLink($guard, (string) $request->email);

        if ($userId === null) {
            $this->incrementAttempts($request);
            Log::info('Password reset requested for non-existent email', [
                'email' => $request->email,
                'guard' => $guard,
                'ip' => $request->ip(),
            ]);
            throw ValidationException::withMessages([
                'email' => [trans(Password::RESET_THROTTLED)],
            ]);
        }

        Log::info('Password reset link sent', [
            'email' => $request->email,
            'guard' => $guard,
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        $this->clearAttempts($request);

        return Inertia::render('Auth/ForgotPassword', [
            'status' => trans(Password::RESET_LINK_SENT),
        ]);
    }

    protected function hasTooManyAttempts(Request $request): bool
    {
        return app(RateLimiter::class)->tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts
        );
    }

    protected function incrementAttempts(Request $request): void
    {
        app(RateLimiter::class)->hit(
            $this->throttleKey($request),
            $this->decayMinutes * 60
        );
    }

    protected function clearAttempts(Request $request): void
    {
        app(RateLimiter::class)->clear($this->throttleKey($request));
    }

    protected function sendLockoutResponse(Request $request): void
    {
        $seconds = app(RateLimiter::class)->availableIn(
            $this->throttleKey($request)
        );

        throw ValidationException::withMessages([
            'email' => [
                __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return 'password_reset|'.mb_strtolower($request->email).'|'.$request->ip();
    }
}
