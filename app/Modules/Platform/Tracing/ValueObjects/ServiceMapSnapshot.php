<?php

namespace App\Modules\Platform\Tracing\ValueObjects;

final readonly class ServiceMapSnapshot
{
    /**
     * @param  array<int, ServiceMapNode>  $nodes
     * @param  array<int, ServiceMapEdge>  $edges
     */
    public function __construct(
        public readonly string $range,
        public readonly int $fromNs,
        public readonly array $nodes,
        public readonly array $edges,
    ) {}
}
