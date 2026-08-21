<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class ServiceMapEdge
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly int $calls,
    ) {}
}
