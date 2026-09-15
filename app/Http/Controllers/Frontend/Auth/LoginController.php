<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
    use ThrottlesAuth;

    protected int $maxAttempts = 5;

    protected int $decayMinutes = 1;

    public function __construct(private readonly CustomerAuthService $authService) {}

    public function __invoke(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        if ($this->authService->authenticate(
            (string) $credentials['email'],
            (string) $credentials['password'],
            $request->boolean('remember'),
        )) {
            $this->clearLoginAttempts($request);
            $request->session()->regenerate();

            return redirect()->intended(route('frontend.home'));
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages(['email' => __('auth.failed')]);
    }
}
