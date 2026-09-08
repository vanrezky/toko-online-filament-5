<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CacheService
{
    /** @var array<string, string> */
    private const MANAGED_GROUPS = [
        'navigation' => 'Navigation',
        'dashboard' => 'Dashboard',
        'template' => 'Template',
        'regional' => 'Regional',
        'voucher' => 'Voucher',
        'product-stats' => 'Product statistics',
        'product-catalog' => 'Product catalog',
        'frontend' => 'Frontend content',
        'shipping' => 'Shipping',
    ];

    public static function remember(string $cacheKey, int $ttl, callable $callback): mixed
    {
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public static function has(string $cacheKey): bool
    {
        return Cache::has($cacheKey);
    }

    public static function get(string $cacheKey, mixed $default = null): mixed
    {
        return Cache::get($cacheKey, $default);
    }

    public static function delete(string $cacheKey): bool
    {
        return Cache::forget($cacheKey);
    }

    public static function rememberManaged(string $group, string $cacheKey, int $ttl, callable $callback): mixed
    {
        self::assertManagedGroup($group);

        return Cache::remember(
            self::managedKey($group, $cacheKey),
            $ttl,
            $callback,
        );
    }

    public static function forgetManaged(string $group, string $cacheKey): bool
    {
        self::assertManagedGroup($group);

        return Cache::forget(self::managedKey($group, $cacheKey));
    }

    public static function getManaged(string $group, string $cacheKey, mixed $default = null): mixed
    {
        self::assertManagedGroup($group);

        return Cache::get(self::managedKey($group, $cacheKey), $default);
    }

    /** @return array<string, string> */
    public static function managedGroups(): array
    {
        return self::MANAGED_GROUPS;
    }

    /** @return array<int, string> */
    public static function clearManaged(): array
    {
        foreach (array_keys(self::MANAGED_GROUPS) as $group) {
            self::clearManagedGroup($group);
        }

        return array_keys(self::MANAGED_GROUPS);
    }

    public static function clearManagedGroup(string $group): void
    {
        self::assertManagedGroup($group);
        Cache::forever(self::versionKey($group), (string) Str::uuid());
    }

    public static function putWithPrefix(string $prefix, string $key, int $ttl, mixed $value): bool
    {
        return Cache::put($prefix.$key, $value, $ttl);
    }

    private static function managedKey(string $group, string $cacheKey): string
    {
        $version = Cache::rememberForever(self::versionKey($group), fn (): string => (string) Str::uuid());

        return "managed:{$group}:{$version}:{$cacheKey}";
    }

    private static function versionKey(string $group): string
    {
        return "cache-management:group:{$group}:version";
    }

    private static function assertManagedGroup(string $group): void
    {
        if (! array_key_exists($group, self::MANAGED_GROUPS)) {
            throw new \InvalidArgumentException("Unknown managed cache group [{$group}].");
        }
    }
}
