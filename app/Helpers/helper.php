<?php

use App\Settings\GeneralSettings;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Retrieve a value from the general settings with an optional default value.
 *
 * @param string $value The key of the setting to retrieve
 * @param mixed $default The default value to return if the setting does not exist
 * @return mixed value of the setting, or the default value if the setting does not exist
 */
function settings(string $key, $default = null)
{
    try {

        if ($key === 'favicon') {
            return app(GeneralSettings::class)->getFavicon();
        }
        if ($key === 'logo') {
            return app(GeneralSettings::class)->getLogo();
        }

        return app(GeneralSettings::class)->$key ?? $default;
    } catch (Throwable $e) {
        Log::error($e);
        return $default;
    }
}

/**
 * Generates a secure password based on settings.
 *
 * @param int $minLength the minimum length of the password (default is 8)
 * @return Password the generated secure password
 */
if (!function_exists('securePassword')) {

    function securePassword(int $minLength = 8)
    {
        return settings('secure_password') ?
            Password::min($minLength)->symbols()->numbers()->letters() :
            Password::min($minLength);
    }
}



if (!function_exists('isSuperUser')) {
    function isSuperUser(): bool
    {
        return auth()->user()->is_super_user ?? false;
    }
}


if (!function_exists('getUrlImage')) {
    function getUrlImage($image): string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            // Jika $image adalah URL eksternal, kembalikan langsung
            return $image;
        }

        return Storage::disk(config('filesystems.upload_disk', 'public'))->url($image);
    }
}

if (!function_exists('toMoney')) {
    function toMoney($price): string
    {
        $amount = is_numeric($price) ? (float) $price : 0;

        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('getActiveDisk')) {
    function getActiveDisk(): string
    {
        return config('filesystems.default');
    }
}

if (!function_exists('noImage')) {
    function noImage(): string
    {
        return asset('assets/images/noimage.jpg');
    }
}
