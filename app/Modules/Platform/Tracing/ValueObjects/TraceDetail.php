<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class TraceDetail
{
    /**
     * @param  array<int, TraceSpan>  $spans
     * @param  array<int, array{span: TraceSpan, children: array}>  $tree
     */
    public function __construct(
        public readonly string $traceId,
        public readonly ?string $correlationId,
        public readonly ?string $rootOperation,
        public readonly ?string $status,
        public readonly int $startNs,
        public readonly ?int $endNs,
        public readonly ?int $durationMs,
        public readonly int $spanCount,
        public readonly int $errorCount,
        public readonly bool $isPartial,
        public readonly array $spans,
        public readonly array $tree,
    ) {}
}
