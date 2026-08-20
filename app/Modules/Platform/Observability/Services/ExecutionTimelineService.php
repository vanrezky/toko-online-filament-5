<?php

namespace App\Modules\Platform\Observability\Services;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\IntegrationLogs\IntegrationLogResource;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Observability\ValueObjects\ExecutionTimeline;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Spatie\Activitylog\Models\Activity;
use Throwable;

final class ExecutionTimelineService
{
    public function __construct(
        private readonly JobRepository $jobs,
    ) {}

    public function timeline(?string $correlationId): ExecutionTimeline
    {
        $integrationEvents = $this->integrationEvents($correlationId);
        $auditEvents = $this->auditEvents($correlationId);
        $queueEvents = $this->queueEvents($correlationId);

        $events = collect([...$integrationEvents, ...$auditEvents, ...$queueEvents])
            ->sortBy(fn (array $event): int => $event['occurred_at'] === null ? PHP_INT_MAX : $event['occurred_at'])
            ->values()
            ->all();

        return new ExecutionTimeline(
            correlationId: $correlationId,
            events: $events,
            integrationCount: count($integrationEvents),
            auditCount: count($auditEvents),
            queueCount: count($queueEvents),
        );
    }

    /**
     * Enumerate distinct correlation IDs from the indexed integration-log column.
     *
     * @return array<int, array{correlation_id: string, last_event_at: ?string}>
     */
    public function list(int $limit = 50): array
    {
        return IntegrationLog::query()
            ->select('correlation_id', DB::raw('MAX(created_at) as last_event_at'))
            ->whereNotNull('correlation_id')
            ->where('correlation_id', '!=', '')
            ->groupBy('correlation_id')
            ->orderByDesc('last_event_at')
            ->limit($limit)
            ->get()
            ->map(fn (IntegrationLog $log): array => [
                'correlation_id' => (string) $log->correlation_id,
                'last_event_at' => $log->last_event_at ? (string) $log->last_event_at : null,
            ])
            ->all();
    }

    /**
     * Resolve a single execution by exact correlation ID, even when it only
     * exists in audit or queue sources.
     *
     * @return array<int, array{correlation_id: string, last_event_at: ?string}>
     */
    public function resolve(string $correlationId): array
    {
        $timeline = $this->timeline($correlationId);

        if ($timeline->isEmpty()) {
            return [];
        }

        $lastEventAt = collect($timeline->events)
            ->map(fn (array $event): ?int => $event['occurred_at'])
            ->filter()
            ->max();

        return [[
            'correlation_id' => $correlationId,
            'last_event_at' => $lastEventAt === null ? null : date('Y-m-d H:i:s', $lastEventAt),
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    private function integrationEvents(?string $correlationId): array
    {
        if (! $correlationId) {
            return [];
        }

        return IntegrationLog::query()
            ->where('correlation_id', $correlationId)
            ->orderBy('started_at')
            ->get()
            ->map(function (IntegrationLog $log): array {
                $occurredAt = $log->started_at?->getTimestamp() ?? $log->created_at?->getTimestamp();

                return [
                    'source' => 'integration',
                    'kind' => 'integration',
                    'title' => sprintf('%s · %s %s', $log->provider, $log->method, $log->endpoint),
                    'description' => $this->integrationDescription($log),
                    'occurred_at' => $occurredAt,
                    'duration_ms' => $log->duration_ms,
                    'status' => $log->status,
                    'link' => IntegrationLogResource::getUrl('view', ['record' => $log]),
                    'correlation_id' => $log->correlation_id,
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function auditEvents(?string $correlationId): array
    {
        if (! $correlationId) {
            return [];
        }

        return Activity::query()
            ->where('properties->context->correlation_id', $correlationId)
            ->orderBy('created_at')
            ->get()
            ->map(function (Activity $activity) use ($correlationId): array {
                return [
                    'source' => 'audit',
                    'kind' => 'audit',
                    'title' => (string) $activity->description,
                    'description' => $activity->event ? 'event: '.$activity->event : null,
                    'occurred_at' => $activity->created_at?->getTimestamp(),
                    'duration_ms' => null,
                    'status' => null,
                    'link' => AuditLogResource::getUrl('view', ['record' => $activity]),
                    'correlation_id' => $correlationId,
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function queueEvents(?string $correlationId): array
    {
        if (! $correlationId) {
            return [];
        }

        try {
            return $this->failedJobsFor($correlationId);
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function failedJobsFor(string $correlationId): array
    {
        $events = [];
        $afterIndex = null;

        do {
            $jobs = $this->jobs->getFailed($afterIndex);

            foreach ($jobs as $job) {
                $payload = json_decode((string) ($job->payload ?? '[]'), true);
                $jobCorrelation = data_get($payload, 'illuminate:log:context.correlation_id');

                if ($jobCorrelation !== $correlationId) {
                    continue;
                }

                $events[] = [
                    'source' => 'queue',
                    'kind' => 'queue',
                    'title' => (string) ($job->name ?? 'job'),
                    'description' => $this->queueDescription($job),
                    'occurred_at' => $this->microtimeToTimestamp($job->failed_at ?? null),
                    'duration_ms' => null,
                    'status' => IntegrationLog::STATUS_FAILED,
                    'link' => null,
                    'correlation_id' => $correlationId,
                ];
            }

            $afterIndex = $jobs->isNotEmpty() ? $afterIndex + $jobs->count() : null;
        } while ($afterIndex !== null && $afterIndex < 1000);

        return $events;
    }

    private function integrationDescription(IntegrationLog $log): ?string
    {
        $parts = array_filter([
            $log->direction === IntegrationLog::DIRECTION_INBOUND ? 'inbound' : 'outbound',
            $log->status_code !== null ? 'http '.$log->status_code : null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    private function queueDescription(object $job): ?string
    {
        $exception = (string) ($job->exception ?? '');

        if ($exception === '') {
            return null;
        }

        return mb_substr($exception, 0, 300);
    }

    private function microtimeToTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (float) str_replace(',', '.', (string) $value);

        return (int) $value;
    }
}
