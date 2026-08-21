<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class ServiceMapNode
{
    public function __construct(
        public readonly string $operation,
        public readonly int $requests,
        public readonly int $errors,
        public readonly ?float $errorRate,
        public readonly ?float $avgMs,
        public readonly ?float $p95Ms,
    ) {}
}
