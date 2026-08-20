<?php

namespace Tests\Feature\Platform\Observability;

use App\Modules\Platform\Health\ValueObjects\HealthMonitorSnapshot;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Observability\Services\ObservabilityService;
use App\Modules\Platform\Queue\ValueObjects\QueueMonitorSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ObservabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_aggregate_metrics_within_the_range(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, 120, $now->subHours(2));
        $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, 180, $now->subHours(3));
        $this->makeLog('apicoid', IntegrationLog::STATUS_FAILED, 500, $now->subHours(4));
        $this->makeLog('apicoid', IntegrationLog::STATUS_FAILED, 900, $now->subHours(30));

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $this->assertSame(3, $snapshot->totalCalls);
        $this->assertSame(1, $snapshot->failedCalls);
        $this->assertSame(267, $snapshot->avgDurationMs);
        $this->assertSame(500, $snapshot->slowestDurationMs);
    }

    public function test_it_groups_provider_performance_with_failed_rate_and_average(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, 100, $now->subHour());
        $this->makeLog('midtrans', IntegrationLog::STATUS_FAILED, 300, $now->subHours(2));
        $this->makeLog('apicoid', IntegrationLog::STATUS_SUCCESS, 200, $now->subHours(3));

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $rows = collect($snapshot->providerPerformance)->keyBy('provider');

        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows['midtrans']['calls']);
        $this->assertSame(1, $rows['midtrans']['failed']);
        $this->assertSame(50.0, $rows['midtrans']['failed_rate']);
        $this->assertSame(200, $rows['midtrans']['avg_duration_ms']);
        $this->assertSame(0.0, $rows['apicoid']['failed_rate']);
    }

    public function test_it_lists_the_slowest_calls_ordered_by_duration_descending(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        foreach ([100, 900, 300] as $duration) {
            $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, $duration, $now->subHour());
        }

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $durations = collect($snapshot->slowCalls)->pluck('duration_ms')->all();

        $this->assertSame([900, 300, 100], $durations);
        $this->assertCount(3, $snapshot->slowCalls);
        $this->assertArrayHasKey('correlation_id', $snapshot->slowCalls[0]);
    }

    public function test_it_lists_the_most_recent_failed_logs(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->makeLog('midtrans', IntegrationLog::STATUS_FAILED, 100, $now->subHour(), ['error_class' => 'TimeoutException', 'error_message' => 'connection timed out']);
        $this->makeLog('apicoid', IntegrationLog::STATUS_FAILED, 100, $now->subHours(2), ['error_class' => 'ConnectionException', 'error_message' => 'host unreachable']);
        $this->makeLog('apicoid', IntegrationLog::STATUS_SUCCESS, 100, $now->subHours(3));

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $this->assertCount(2, $snapshot->recentErrors);
        $this->assertSame('TimeoutException', $snapshot->recentErrors[0]['error_class']);
        $this->assertSame('apicoid', $snapshot->recentErrors[1]['provider']);
    }

    public function test_it_bounds_results_to_the_selected_range(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, 100, $now->subDays(2));
        $this->makeLog('midtrans', IntegrationLog::STATUS_FAILED, 100, $now->subDays(10));

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $this->assertSame(0, $snapshot->totalCalls);
        $this->assertSame(0, $snapshot->failedCalls);
        $this->assertNull($snapshot->avgDurationMs);
        $this->assertNull($snapshot->slowestDurationMs);
        $this->assertSame([], $snapshot->providerPerformance);
        $this->assertSame([], $snapshot->slowCalls);
        $this->assertSame([], $snapshot->recentErrors);
    }

    public function test_it_defaults_to_the_last_24_hours_without_an_explicit_range(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->makeLog('midtrans', IntegrationLog::STATUS_SUCCESS, 100, $now->subHour());
        $this->makeLog('apicoid', IntegrationLog::STATUS_FAILED, 100, $now->subHours(30));

        $snapshot = app(ObservabilityService::class)->snapshot();

        $this->assertSame(1, $snapshot->totalCalls);
        $this->assertSame(0, $snapshot->failedCalls);
    }

    public function test_it_exposes_queue_and_health_summaries(): void
    {
        $snapshot = app(ObservabilityService::class)->snapshot();

        $this->assertInstanceOf(QueueMonitorSnapshot::class, $snapshot->queue);
        $this->assertInstanceOf(HealthMonitorSnapshot::class, $snapshot->health);
    }

    public function test_it_limits_slow_calls_and_recent_errors(): void
    {
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        for ($i = 0; $i < 15; $i++) {
            $this->makeLog('midtrans', IntegrationLog::STATUS_FAILED, 100 + $i, $now->subMinutes($i));
        }

        $snapshot = app(ObservabilityService::class)->snapshot($now->subDay(), $now);

        $this->assertCount(ObservabilityService::SLOW_CALLS_LIMIT, $snapshot->slowCalls);
        $this->assertCount(ObservabilityService::RECENT_ERRORS_LIMIT, $snapshot->recentErrors);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeLog(string $provider, string $status, int $duration, CarbonImmutable $createdAt, array $extra = []): IntegrationLog
    {
        return IntegrationLog::query()->forceCreate(array_merge([
            'direction' => IntegrationLog::DIRECTION_OUTBOUND,
            'provider' => $provider,
            'type' => IntegrationLog::TYPE_API,
            'method' => 'GET',
            'endpoint' => '/v1/test',
            'status' => $status,
            'duration_ms' => $duration,
            'correlation_id' => (string) Str::uuid(),
            'started_at' => $createdAt->subSeconds(1),
            'created_at' => $createdAt,
        ], $extra));
    }
}
