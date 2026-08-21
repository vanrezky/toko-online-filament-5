<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    public function test_managed_cache_is_invalidated_without_flushing_the_store(): void
    {
        Cache::shouldReceive('rememberForever')
            ->once()
            ->andReturn('version-one');
        Cache::shouldReceive('remember')
            ->once()
            ->with('managed:dashboard:version-one:stats', 60, \Mockery::type('callable'))
            ->andReturn('value');
        Cache::shouldReceive('forever')
            ->once()
            ->with('cache-management:group:dashboard:version', \Mockery::type('string'));

        self::assertSame('value', CacheService::rememberManaged('dashboard', 'stats', 60, fn () => 'value'));
        CacheService::clearManagedGroup('dashboard');
    }

    public function test_unknown_group_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CacheService::rememberManaged('unknown', 'key', 60, fn () => 'value');
    }

    public function test_managed_cache_source_contains_no_broad_redis_flush_operations(): void
    {
        $source = file_get_contents(app_path('Services/CacheService.php'));

        self::assertIsString($source);
        self::assertStringNotContainsString('FLUSHALL', $source);
        self::assertStringNotContainsString('FLUSHDB', $source);
        self::assertStringNotContainsString('Redis::scan', $source);
        self::assertStringNotContainsString('Redis::command', $source);
    }
}
