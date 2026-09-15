<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Services\CustomerAuthService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class RegisterController extends Controller
{
    protected int $maxAttempts = 5;

    protected int $decayMinutes = 30;

    public function __construct(private readonly CustomerAuthService $authService) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($this->registrationIsClosed()) {
            return redirect()->route('frontend.registration-closed');
        }

        return Inertia::render('Auth/Register', [
            'secure_password' => (bool) settings('secure_password'),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if ($this->registrationIsClosed()) {
            abort(403);
        }

        if ($this->hasTooManyAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => ['required', 'confirmed', securePassword(8)],
        ];

        if (settings('term_agreement', false)) {
            $rules['terms_accepted'] = ['required', 'accepted'];
        }

        $validated = $request->validate($rules);

        $this->authService->register([
            'first_name' => (string) $validated['first_name'],
            'last_name' => (string) $validated['last_name'],
            'email' => (string) $validated['email'],
            'password' => (string) $validated['password'],
        ]);
        $this->clearAttempts($request);

        return redirect()->route('frontend.home');
    }

    private function hasTooManyAttempts(Request $request): bool
    {
        return app(RateLimiter::class)->tooManyAttempts($this->throttleKey($request), $this->maxAttempts);
    }

    private function incrementAttempts(Request $request): void
    {
        app(RateLimiter::class)->hit($this->throttleKey($request), $this->decayMinutes * 60);
    }

    private function clearAttempts(Request $request): void
    {
        app(RateLimiter::class)->clear($this->throttleKey($request));
    }

    private function sendLockoutResponse(Request $request): void
    {
        $seconds = app(RateLimiter::class)->availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [__('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'register|'.$request->ip();
    }

    private function registrationIsClosed(): bool
    {
        return (bool) settings('is_private_store', false) || ! (bool) settings('registration', true);
    }
}
