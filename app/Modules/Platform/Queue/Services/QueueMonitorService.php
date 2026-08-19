<?php

namespace App\Modules\Platform\Queue\Services;

use App\Modules\Platform\Queue\ValueObjects\QueueMonitorSnapshot;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Throwable;

final class QueueMonitorService
{
    public function __construct(
        private readonly JobRepository $jobs,
        private readonly MasterSupervisorRepository $masterSupervisors,
        private readonly WorkloadRepository $workloads,
    ) {}

    public function snapshot(): QueueMonitorSnapshot
    {
        try {
            $masters = $this->masterSupervisors->all();
            $workload = collect($this->workloads->get())
                ->map(fn (array $queue): array => [
                    'name' => (string) Arr::get($queue, 'name'),
                    'length' => (int) Arr::get($queue, 'length', 0),
                    'wait' => (int) Arr::get($queue, 'wait', 0),
                    'processes' => (int) Arr::get($queue, 'processes', 0),
                ])
                ->values()
                ->all();

            return new QueueMonitorSnapshot(
                status: $this->statusFor($masters),
                pendingJobs: $this->jobs->countPending(),
                failedJobs: $this->jobs->countFailed(),
                processedJobs: $this->jobs->countCompleted(),
                queues: collect($workload)->pluck('name')->filter()->values()->all(),
                workload: $workload,
                recentFailedJobs: $this->recentFailedJobs(),
            );
        } catch (Throwable) {
            return QueueMonitorSnapshot::unavailable();
        }
    }

    /** @return array<int, array{id: string, name: string, failed_at: mixed, correlation_id: string|null}> */
    private function recentFailedJobs(): array
    {
        return $this->jobs->getFailed()
            ->sortByDesc('failed_at')
            ->take(10)
            ->map(function (object $job): array {
                $payload = json_decode((string) ($job->payload ?? '[]'), true);

                return [
                    'id' => (string) ($job->id ?? ''),
                    'name' => (string) ($job->name ?? ''),
                    'failed_at' => $job->failed_at ?? null,
                    'correlation_id' => data_get($payload, 'illuminate:log:context.correlation_id'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $masters
     */
    private function statusFor(array $masters): string
    {
        if ($masters === []) {
            return 'no_workers';
        }

        $hasRunningMaster = collect($masters)->contains(
            fn (object|array $master): bool => data_get($master, 'status') !== 'paused',
        );

        return $hasRunningMaster
            ? 'running'
            : 'offline';
    }
}
