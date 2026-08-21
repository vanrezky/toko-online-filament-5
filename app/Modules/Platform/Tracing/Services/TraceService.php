<?php

namespace App\Modules\Platform\Tracing\Services;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\ValueObjects\TraceDetail;
use App\Modules\Platform\Tracing\ValueObjects\TraceSummary;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class TraceService
{
    public function __construct(
        private readonly TraceSpanService $spanService,
    ) {}

    /**
     * Paginated trace list with backend-applied filters.
     *
     * @param  array{
     *     from_ns?: int,
     *     until_ns?: int,
     *     status?: ?string,
     *     operation?: ?string,
     *     duration_min?: ?int,
     *     duration_max?: ?int,
     *     has_errors?: bool,
     *     correlation_id?: ?string,
     *     search?: ?string,
     * }  $filters
     * @return LengthAwarePaginator<int, TraceSummary>
     */
    public function list(array $filters = [], int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $query = TracingSpan::query()
            ->select([
                'trace_id',
                DB::raw('MIN(start_ns) as start_ns'),
                DB::raw('MAX(end_ns) as end_ns'),
                DB::raw('MAX(CASE WHEN parent_span_id IS NULL THEN operation END) as root_operation'),
                DB::raw('MAX(CASE WHEN parent_span_id IS NULL THEN UPPER(status_code) END) as root_status'),
                DB::raw('MAX(CASE WHEN parent_span_id IS NULL THEN correlation_id END) as correlation_id'),
                DB::raw('COUNT(*) as span_count'),
                DB::raw('SUM(UPPER(status_code) = "ERROR") as error_count'),
                DB::raw('SUM(CASE WHEN end_ns IS NULL THEN 1 ELSE 0 END) as incomplete_count'),
                DB::raw('(MAX(end_ns) - MIN(start_ns)) / 1000000 as duration_ms'),
            ])
            ->groupBy('trace_id');

        if (isset($filters['from_ns'])) {
            $query->where('start_ns', '>=', $filters['from_ns']);
        }

        if (isset($filters['until_ns'])) {
            $query->where('start_ns', '<', $filters['until_ns']);
        }

        if (! empty($filters['status'])) {
            $query->having('root_status', '=', $filters['status']);
        }

        if (! empty($filters['operation'])) {
            $query->having('root_operation', 'like', '%'.$filters['operation'].'%');
        }

        if (isset($filters['duration_min'])) {
            $query->having('duration_ms', '>=', $filters['duration_min']);
        }

        if (isset($filters['duration_max'])) {
            $query->having('duration_ms', '<=', $filters['duration_max']);
        }

        if (! empty($filters['has_errors'])) {
            $query->having('error_count', '>', 0);
        }

        if (! empty($filters['correlation_id'])) {
            $query->where('correlation_id', $filters['correlation_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('trace_id', 'like', '%'.$search.'%')
                    ->orWhere('correlation_id', 'like', '%'.$search.'%');
            });
        }

        $query->orderByDesc('start_ns');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $paginated->setCollection($paginated->getCollection()->map(
            fn ($row): TraceSummary => $this->toSummary($row),
        ));

        return $paginated;
    }

    public function resolve(string $traceId): ?TraceDetail
    {
        $spans = TracingSpan::query()
            ->where('trace_id', $traceId)
            ->orderBy('start_ns')
            ->get();

        if ($spans->isEmpty()) {
            return null;
        }

        $startNs = (int) $spans->min('start_ns');
        $endNs = $spans->filter(fn (TracingSpan $span): bool => $span->end_ns !== null)
            ->map(fn (TracingSpan $span): int => (int) $span->end_ns)
            ->max();

        $root = $spans->first(fn (TracingSpan $span): bool => $span->parent_span_id === null) ?? $spans->first();

        $decorated = $this->spanService->decorate($spans);

        return new TraceDetail(
            traceId: $traceId,
            correlationId: $root->correlation_id,
            rootOperation: $this->rootOperation($root),
            status: $root->status_code,
            startNs: $startNs,
            endNs: $endNs,
            durationMs: $endNs !== null ? (int) round(($endNs - $startNs) / 1_000_000) : null,
            spanCount: $spans->count(),
            errorCount: $spans->where('status_code', TracingSpan::STATUS_ERROR)->count(),
            isPartial: $spans->contains(fn (TracingSpan $span): bool => $span->end_ns === null),
            spans: $decorated,
            tree: $this->spanService->tree($decorated),
        );
    }

    private function toSummary(mixed $row): TraceSummary
    {
        $durationMs = $row->duration_ms !== null ? (int) round((float) $row->duration_ms) : null;

        return new TraceSummary(
            traceId: (string) $row->trace_id,
            correlationId: $row->correlation_id !== null ? (string) $row->correlation_id : null,
            rootOperation: $row->root_operation !== null ? (string) $row->root_operation : null,
            status: $row->root_status !== null ? (string) $row->root_status : null,
            startNs: (int) $row->start_ns,
            durationMs: $durationMs,
            spanCount: (int) $row->span_count,
            errorCount: (int) $row->error_count,
            isPartial: (int) $row->incomplete_count > 0,
        );
    }

    private function rootOperation(TracingSpan $span): ?string
    {
        return $span->operation ?? $span->name;
    }
}
