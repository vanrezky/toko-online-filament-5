<?php

namespace App\Modules\Platform\Tracing\Services;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\ValueObjects\ServiceMapEdge;
use App\Modules\Platform\Tracing\ValueObjects\ServiceMapNode;
use App\Modules\Platform\Tracing\ValueObjects\ServiceMapSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ServiceMapService
{
    public const RANGES = [
        '1h' => 1,
        '24h' => 24,
        '7d' => 168,
    ];

    /**
     * Build a read-only service map from actual recorded parent-child span
     * relationships, with metrics aggregated at the database level for the
     * selected time range.
     */
    public function map(string $range = '24h', ?CarbonImmutable $now = null): ServiceMapSnapshot
    {
        $hours = self::RANGES[$range] ?? self::RANGES['24h'];
        $fromNs = (($now ?? CarbonImmutable::now())->getTimestamp()) * 1_000_000_000 - $hours * 3_600_000_000_000;

        $nodeRows = $this->nodeRows($fromNs);
        $p95ByOperation = $this->p95ByOperation($fromNs);
        $edgeRows = $this->edgeRows($fromNs);

        $nodes = collect($nodeRows)->map(function (mixed $row) use ($p95ByOperation): ServiceMapNode {
            $operation = (string) $row->operation;
            $requests = (int) $row->requests;
            $errors = (int) $row->errors;
            $avgMs = $row->avg_ms !== null ? round((float) $row->avg_ms, 2) : null;
            $p95Ms = isset($p95ByOperation[$operation]) ? round((float) $p95ByOperation[$operation], 2) : null;

            return new ServiceMapNode(
                operation: $operation,
                requests: $requests,
                errors: $errors,
                errorRate: $requests > 0 ? round($errors / $requests, 4) : null,
                avgMs: $avgMs,
                p95Ms: $p95Ms,
            );
        })->sortByDesc(fn (ServiceMapNode $node): int => $node->requests)->values()->all();

        $edges = collect($edgeRows)->map(
            fn (mixed $row): ServiceMapEdge => new ServiceMapEdge(
                from: (string) $row->from_operation,
                to: (string) $row->to_operation,
                calls: (int) $row->calls,
            ),
        )->all();

        return new ServiceMapSnapshot(
            range: $range,
            fromNs: $fromNs,
            nodes: $nodes,
            edges: $edges,
        );
    }

    /** @return Collection<int, mixed> */
    private function nodeRows(int $fromNs): Collection
    {
        return TracingSpan::query()
            ->where('start_ns', '>=', $fromNs)
            ->whereNotNull('operation')
            ->where('operation', '!=', '')
            ->selectRaw('operation, COUNT(*) as requests, SUM(UPPER(status_code) = "ERROR") as errors, AVG(duration_ms) as avg_ms')
            ->groupBy('operation')
            ->get();
    }

    /**
     * Approximate P95 per operation using a DB-level NTILE window (bounded,
     * no full result set loaded). Operations with too few spans to yield a
     * 95th bucket are simply absent, so the metric is only shown when it can
     * be calculated.
     *
     * @return array<string, string>
     */
    private function p95ByOperation(int $fromNs): array
    {
        $ranked = DB::table('tracing_spans')
            ->selectRaw('operation, duration_ms, NTILE(100) OVER (PARTITION BY operation ORDER BY duration_ms) AS pct')
            ->where('start_ns', '>=', $fromNs)
            ->whereNotNull('operation')
            ->where('operation', '!=', '')
            ->whereNotNull('duration_ms');

        $rows = DB::table($ranked, 'ranked')
            ->where('pct', '>=', 95)
            ->selectRaw('operation, MIN(duration_ms) as p95_ms')
            ->groupBy('operation')
            ->get();

        return collect($rows)->pluck('p95_ms', 'operation')->all();
    }

    /** @return Collection<int, mixed> */
    private function edgeRows(int $fromNs): Collection
    {
        return DB::table('tracing_spans as child')
            ->join('tracing_spans as parent', 'child.parent_span_id', '=', 'parent.span_id')
            ->where('child.start_ns', '>=', $fromNs)
            ->whereNotNull('child.operation')
            ->where('child.operation', '!=', '')
            ->whereNotNull('parent.operation')
            ->where('parent.operation', '!=', '')
            ->selectRaw('parent.operation as from_operation, child.operation as to_operation, COUNT(*) as calls')
            ->groupBy('from_operation', 'to_operation')
            ->orderByDesc('calls')
            ->get();
    }
}
