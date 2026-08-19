<?php

namespace App\Modules\Platform\Queue\ValueObjects;

final readonly class QueueMonitorSnapshot
{
    /**
     * @param  array<int, string>  $queues
     * @param  array<int, array{name: string, length: int, wait: int, processes: int}>  $workload
     * @param  array<int, array{id: string, name: string, failed_at: mixed, correlation_id: string|null}>  $recentFailedJobs
     */
    public function __construct(
        public string $status,
        public int $pendingJobs,
        public int $failedJobs,
        public ?int $processedJobs,
        public array $queues,
        public array $workload,
        public array $recentFailedJobs = [],
    ) {}

    public static function unavailable(): self
    {
        return new self(
            status: 'unavailable',
            pendingJobs: 0,
            failedJobs: 0,
            processedJobs: null,
            queues: [],
            workload: [],
            recentFailedJobs: [],
        );
    }

    public function hasWorkers(): bool
    {
        return $this->status === 'running';
    }

    public function statusLabel(): string
    {
        return __('admin/queue-monitor-page.status.'.$this->status);
    }
}
