<?php

namespace App\Modules\Platform\Observability\ValueObjects;

final readonly class ExecutionTimeline
{
    /**
     * @param  array<int, array{
     *     source: string,
     *     kind: string,
     *     title: string,
     *     description: ?string,
     *     occurred_at: ?string,
     *     duration_ms: ?int,
     *     status: ?string,
     *     link: ?string,
     *     correlation_id: ?string,
     * }>  $events
     */
    public function __construct(
        public readonly ?string $correlationId,
        public readonly array $events,
        public readonly int $integrationCount,
        public readonly int $auditCount,
        public readonly int $queueCount,
    ) {}

    public function isEmpty(): bool
    {
        return $this->events === [];
    }
}
