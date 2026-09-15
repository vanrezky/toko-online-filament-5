<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend\Auth;

use App\Http\Controllers\Controller;
use App\Services\CustomerAuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class SocialLoginController extends Controller
{
    private const PROVIDERS = ['google', 'github'];

    public function __construct(private readonly CustomerAuthService $authService) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);
        $this->ensureSocialLoginIsAvailable();

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);
        $this->ensureSocialLoginIsAvailable();

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('frontend.login')->with('error', __('auth.social_login_failed'));
        }

        if (blank($socialUser->getEmail())) {
            return redirect()->route('frontend.login')->with('error', __('auth.social_email_required'));
        }

        $this->authService->loginSocial($socialUser, $provider);
        request()->session()->regenerate();

        return redirect()->intended(route('frontend.home'));
    }

    private function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }

    private function ensureSocialLoginIsAvailable(): void
    {
        abort_unless(settings('social_login_enabled', true), 404);
        abort_if((bool) settings('is_private_store', false), 403);
    }
}
