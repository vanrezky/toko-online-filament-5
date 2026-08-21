<?php

namespace App\Modules\Platform\Cache\Services;

use App\Modules\Platform\Cache\ValueObjects\CacheManagementSnapshot;
use App\Modules\Platform\Health\Services\HealthMonitorService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class CacheManagementService
{
    public function __construct(private readonly HealthMonitorService $healthMonitor) {}

    public function snapshot(): CacheManagementSnapshot
    {
        $store = (string) config('cache.default');
        $storeConfig = (array) config("cache.stores.{$store}", []);
        $driver = (string) ($storeConfig['driver'] ?? $store);
        $connection = $driver === 'redis' ? ($storeConfig['connection'] ?? null) : null;
        $metadata = array_filter([
            'store' => $driver,
            'default_store' => $store,
            'prefix' => config('cache.prefix'),
            'connection' => is_scalar($connection) ? (string) $connection : null,
        ], fn (?string $value): bool => filled($value));

        $healthStatus = $this->matchingHealthStatus($connection);

        if ($healthStatus !== null) {
            return new CacheManagementSnapshot($healthStatus, $metadata);
        }

        try {
            $probeKey = 'cache-management:health-probe';
            $cache = Cache::store($store);
            $cache->put($probeKey, true, 5);
            $available = $cache->get($probeKey) === true;
            $cache->forget($probeKey);

            return new CacheManagementSnapshot(
                $available ? 'connected' : 'unavailable',
                $metadata,
                $available ? null : __('admin/cache-management-page.messages.probe_failed'),
            );
        } catch (Throwable) {
            return new CacheManagementSnapshot(
                'unavailable',
                $metadata,
                __('admin/cache-management-page.messages.unavailable'),
            );
        }
    }

    /** @return array{cleared: array<int, string>, failed: array<int, string>} */
    public function clearManaged(): array
    {
        $cleared = [];
        $failed = [];

        foreach (array_keys(CacheService::managedGroups()) as $group) {
            try {
                CacheService::clearManagedGroup($group);
                $cleared[] = $group;
            } catch (Throwable) {
                $failed[] = $group;
            }
        }

        return compact('cleared', 'failed');
    }

    /** @return array{cleared: array<int, string>, failed: array<int, string>} */
    public function clearGroup(string $group): array
    {
        if (! array_key_exists($group, CacheService::managedGroups())) {
            throw new \InvalidArgumentException("Unknown managed cache group [{$group}].");
        }

        try {
            CacheService::clearManagedGroup($group);

            return ['cleared' => [$group], 'failed' => []];
        } catch (Throwable) {
            return ['cleared' => [], 'failed' => [$group]];
        }
    }

    private function matchingHealthStatus(?string $cacheConnection): ?string
    {
        if ($cacheConnection === null) {
            return null;
        }

        $redisCheck = collect($this->healthMonitor->summary()->checks)
            ->firstWhere('name', 'redis');

        if (($redisCheck['meta']['connection_name'] ?? null) !== $cacheConnection) {
            return null;
        }

        return match ($redisCheck['status'] ?? 'unknown') {
            'healthy' => 'connected',
            'failed' => 'unavailable',
            default => null,
        };
    }
}
