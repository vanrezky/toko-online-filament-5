<?php

namespace App\Modules\Platform\Observability\ValueObjects;

use App\Modules\Platform\Health\ValueObjects\HealthMonitorSnapshot;
use App\Modules\Platform\Queue\ValueObjects\QueueMonitorSnapshot;
use Carbon\CarbonImmutable;

final readonly class ObservabilitySnapshot
{
    /**
     * @param  array<int, array{provider: string, calls: int, failed: int, failed_rate: float, avg_duration_ms: ?int}>  $providerPerformance
     * @param  array<int, array{provider: string, endpoint: ?string, duration_ms: int, status: string, correlation_id: string}>  $slowCalls
     * @param  array<int, array{provider: string, endpoint: ?string, error_class: ?string, error_message: ?string, created_at: string, correlation_id: string}>  $recentErrors
     */
    public function __construct(
        public readonly int $totalCalls,
        public readonly int $failedCalls,
        public readonly ?int $avgDurationMs,
        public readonly ?int $slowestDurationMs,
        public readonly array $providerPerformance,
        public readonly array $slowCalls,
        public readonly array $recentErrors,
        public readonly QueueMonitorSnapshot $queue,
        public readonly HealthMonitorSnapshot $health,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $until,
    ) {}
}
