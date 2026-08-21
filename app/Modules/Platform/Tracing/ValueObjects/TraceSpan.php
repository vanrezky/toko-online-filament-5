<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class TraceSpan
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{name: string, epoch_ns: int, attributes: array<string, mixed>}>  $events
     */
    public function __construct(
        public readonly string $traceId,
        public readonly string $spanId,
        public readonly ?string $parentSpanId,
        public readonly string $name,
        public readonly int $kind,
        public readonly ?string $statusCode,
        public readonly ?string $statusDescription,
        public readonly int $startNs,
        public readonly ?int $endNs,
        public readonly ?int $durationMs,
        public readonly ?string $operation,
        public readonly ?string $correlationId,
        public readonly array $attributes,
        public readonly array $events,
        public readonly ?string $integrationLogUrl,
        public readonly float $leftPercent,
        public readonly float $widthPercent,
        public readonly bool $isRoot,
        public readonly bool $isFailed,
        public readonly bool $isSlow,
    ) {}
}
