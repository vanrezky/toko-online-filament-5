<?php

namespace Tests\Unit\Modules\Platform\Cache;

use App\Modules\Platform\Cache\Services\CacheManagementService;
use App\Modules\Platform\Health\Services\HealthMonitorService;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CacheManagementServiceTest extends TestCase
{
    public function test_clear_only_rotates_declared_application_cache_groups(): void
    {
        foreach (array_keys(CacheService::managedGroups()) as $group) {
            Cache::shouldReceive('forever')
                ->once()
                ->with("cache-management:group:{$group}:version", Mockery::type('string'));
        }

        $service = new CacheManagementService(app(HealthMonitorService::class));

        $result = $service->clearManaged();

        self::assertSame(array_keys(CacheService::managedGroups()), $result['cleared']);
        self::assertSame([], $result['failed']);
    }

    public function test_clearing_managed_cache_does_not_remove_unrelated_cache_data(): void
    {
        Cache::store('array')->put('unrelated-runtime-data', 'keep', 60);
        CacheService::rememberManaged('dashboard', 'stats', 60, fn (): string => 'before');

        CacheService::clearManaged();

        self::assertSame('keep', Cache::store('array')->get('unrelated-runtime-data'));
        self::assertSame('after', CacheService::rememberManaged('dashboard', 'stats', 60, fn (): string => 'after'));
    }

    public function test_clear_group_only_rotates_the_selected_group(): void
    {
        Cache::shouldReceive('forever')
            ->once()
            ->with('cache-management:group:frontend:version', Mockery::type('string'));

        $service = new CacheManagementService(app(HealthMonitorService::class));

        self::assertSame(
            ['cleared' => ['frontend'], 'failed' => []],
            $service->clearGroup('frontend'),
        );
    }

    public function test_clear_group_rejects_unknown_group(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(CacheManagementService::class)->clearGroup('arbitrary-key');
    }
}
