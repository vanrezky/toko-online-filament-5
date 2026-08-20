<?php

namespace App\Modules\Platform\Observability\Services;

use App\Modules\Platform\Health\Services\HealthMonitorService;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Observability\ValueObjects\ObservabilitySnapshot;
use App\Modules\Platform\Queue\Services\QueueMonitorService;
use Carbon\CarbonImmutable;

final class ObservabilityService
{
    public const SLOW_CALLS_LIMIT = 10;

    public const RECENT_ERRORS_LIMIT = 10;

    public function __construct(
        private readonly QueueMonitorService $queue,
        private readonly HealthMonitorService $health,
    ) {}

    public function snapshot(?CarbonImmutable $from = null, ?CarbonImmutable $until = null): ObservabilitySnapshot
    {
        $from ??= now()->subDay()->toImmutable();
        $until ??= now()->toImmutable();

        $logs = IntegrationLog::query()->whereBetween('created_at', [$from, $until]);

        $stats = (clone $logs)->toBase()->selectRaw('COUNT(*) as total_calls, SUM(status = ?) as failed_calls, AVG(duration_ms) as avg_duration_ms, MAX(duration_ms) as slowest_duration_ms')
            ->addBinding(IntegrationLog::STATUS_FAILED, 'select')
            ->first();

        $providerPerformance = (clone $logs)->toBase()
            ->selectRaw('provider, COUNT(*) as calls, SUM(status = ?) as failed, AVG(duration_ms) as avg_duration_ms')
            ->addBinding(IntegrationLog::STATUS_FAILED, 'select')
            ->groupBy('provider')
            ->orderByDesc('calls')
            ->get()
            ->map(fn (object $row): array => [
                'provider' => (string) $row->provider,
                'calls' => (int) $row->calls,
                'failed' => (int) $row->failed,
                'failed_rate' => $row->calls > 0 ? round(((int) $row->failed / (int) $row->calls) * 100, 1) : 0.0,
                'avg_duration_ms' => $row->avg_duration_ms === null ? null : (int) round((float) $row->avg_duration_ms),
            ])
            ->all();

        $slowCalls = (clone $logs)->toBase()
            ->select('provider', 'endpoint', 'duration_ms', 'status', 'correlation_id')
            ->whereNotNull('duration_ms')
            ->orderByDesc('duration_ms')
            ->limit(self::SLOW_CALLS_LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'provider' => (string) $row->provider,
                'endpoint' => $row->endpoint,
                'duration_ms' => (int) $row->duration_ms,
                'status' => (string) $row->status,
                'correlation_id' => (string) $row->correlation_id,
            ])
            ->all();

        $recentErrors = (clone $logs)->toBase()
            ->select('provider', 'endpoint', 'error_class', 'error_message', 'created_at', 'correlation_id')
            ->where('status', IntegrationLog::STATUS_FAILED)
            ->orderByDesc('created_at')
            ->limit(self::RECENT_ERRORS_LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'provider' => (string) $row->provider,
                'endpoint' => $row->endpoint,
                'error_class' => $row->error_class,
                'error_message' => $row->error_message,
                'created_at' => (string) $row->created_at,
                'correlation_id' => (string) $row->correlation_id,
            ])
            ->all();

        return new ObservabilitySnapshot(
            totalCalls: (int) ($stats->total_calls ?? 0),
            failedCalls: (int) ($stats->failed_calls ?? 0),
            avgDurationMs: $stats->avg_duration_ms === null ? null : (int) round((float) $stats->avg_duration_ms),
            slowestDurationMs: $stats->slowest_duration_ms === null ? null : (int) $stats->slowest_duration_ms,
            providerPerformance: $providerPerformance,
            slowCalls: $slowCalls,
            recentErrors: $recentErrors,
            queue: $this->queue->snapshot(),
            health: $this->health->summary(),
            from: $from,
            until: $until,
        );
    }
}
