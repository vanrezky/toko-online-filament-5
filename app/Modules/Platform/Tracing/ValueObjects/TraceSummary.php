<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class TraceSummary
{
    public function __construct(
        public readonly string $traceId,
        public readonly ?string $correlationId,
        public readonly ?string $rootOperation,
        public readonly ?string $status,
        public readonly int $startNs,
        public readonly ?int $durationMs,
        public readonly int $spanCount,
        public readonly int $errorCount,
        public readonly bool $isPartial,
    ) {}
}
