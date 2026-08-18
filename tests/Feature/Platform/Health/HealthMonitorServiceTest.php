<?php

namespace Tests\Feature\Platform\Health;

use App\Modules\Platform\Health\Services\HealthMonitorService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use RuntimeException;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;
use Tests\TestCase;

class HealthMonitorServiceTest extends TestCase
{
    public function test_it_normalizes_stored_health_results(): void
    {
        $store = Mockery::mock(ResultStore::class);

        $store->shouldReceive('latestResults')->once()->andReturn(new StoredCheckResults(
            Carbon::now(),
            new Collection([
                new StoredCheckResult('application', 'Application', null, '', 'ok', ['environment' => 'testing']),
                new StoredCheckResult('database', 'Database', null, '', 'ok', ['connection_name' => 'mysql']),
                new StoredCheckResult('redis', 'Redis', null, '', 'ok', ['connection_name' => 'default']),
                new StoredCheckResult('queue', 'Queue', null, '', 'warning', []),
                new StoredCheckResult('disk', 'Disk', null, '', 'ok', ['disk_space_used_percentage' => 40]),
            ]),
        ));

        $snapshot = (new HealthMonitorService($store))->summary();

        $this->assertTrue($snapshot->available);
        $this->assertSame('warning', $snapshot->overall);
        $this->assertSame('healthy', $snapshot->checks[1]['status']);
        $this->assertSame(['connection_name' => 'default'], $snapshot->checks[2]['meta']);
    }

    public function test_it_returns_unknown_snapshot_when_results_are_unavailable(): void
    {
        $store = Mockery::mock(ResultStore::class);

        $store->shouldReceive('latestResults')->once()->andThrow(new RuntimeException('Redis password=secret'));

        $snapshot = (new HealthMonitorService($store))->summary();

        $this->assertFalse($snapshot->available);
        $this->assertSame('unknown', $snapshot->overall);
        $this->assertCount(5, $snapshot->checks);
        $this->assertSame('unknown', $snapshot->checks[2]['status']);
    }
}
