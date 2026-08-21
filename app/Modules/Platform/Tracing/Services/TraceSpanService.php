<?php

namespace App\Modules\Platform\Tracing\Services;

use App\Filament\Resources\IntegrationLogs\IntegrationLogResource;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\ValueObjects\TraceSpan;
use Illuminate\Database\Eloquent\Collection;
use OpenTelemetry\API\Trace\SpanKind;

final class TraceSpanService
{
    /**
     * Decorate raw span rows with waterfall geometry (computed from the
     * actual start/end timestamps relative to the trace start) and, for
     * client spans, a link to the matching Integration Log when one exists.
     *
     * @param  Collection<int, TracingSpan>  $spans
     * @return array<int, TraceSpan>
     */
    public function decorate(Collection $spans): array
    {
        $traceStart = (int) $spans->min('start_ns');
        $traceEnd = (int) $spans->filter(fn (TracingSpan $span): bool => $span->end_ns !== null)
            ->map(fn (TracingSpan $span): int => (int) $span->end_ns)
            ->max();

        $traceTotal = max(1, $traceEnd - $traceStart);

        $slowThreshold = (int) config('tracing.slow_span_threshold_ms', 1000);

        $logsByCorrelation = $this->integrationLogsByCorrelation($spans);

        return $spans->map(function (TracingSpan $span) use ($traceStart, $traceTotal, $slowThreshold, $logsByCorrelation): TraceSpan {
            $spanEnd = $span->end_ns !== null ? (int) $span->end_ns : (int) $span->start_ns;
            $duration = max(0, $spanEnd - (int) $span->start_ns);

            $leftPercent = (float) (($span->start_ns - $traceStart) / $traceTotal) * 100;
            $widthPercent = $duration === 0
                ? 0.5
                : max(0.5, (float) ($duration / $traceTotal) * 100);

            $isFailed = $span->status_code === TracingSpan::STATUS_ERROR;
            $isSlow = $span->duration_ms !== null && $span->duration_ms >= $slowThreshold;

            return new TraceSpan(
                traceId: (string) $span->trace_id,
                spanId: (string) $span->span_id,
                parentSpanId: $span->parent_span_id,
                name: (string) $span->name,
                kind: (int) $span->kind,
                statusCode: $span->status_code,
                statusDescription: $span->status_description,
                startNs: (int) $span->start_ns,
                endNs: $span->end_ns !== null ? (int) $span->end_ns : null,
                durationMs: $span->duration_ms,
                operation: $span->operation,
                correlationId: $span->correlation_id,
                attributes: $span->attributes ?? [],
                events: $span->events ?? [],
                integrationLogUrl: $this->integrationLogUrl($span, $logsByCorrelation),
                leftPercent: $leftPercent,
                widthPercent: $widthPercent,
                isRoot: $span->parent_span_id === null,
                isFailed: $isFailed,
                isSlow: $isSlow,
            );
        })->values()->all();
    }

    /**
     * Build a nested tree from the recorded parent-child relationships.
     *
     * @param  array<int, TraceSpan>  $spans
     * @return array<int, array{span: TraceSpan, children: array}>
     */
    public function tree(array $spans): array
    {
        $bySpanId = [];
        foreach ($spans as $span) {
            $bySpanId[$span->spanId] = $span;
        }

        $roots = array_values(array_filter(
            $spans,
            fn (TraceSpan $span): bool => $span->isRoot || $span->parentSpanId === null || ! isset($bySpanId[$span->parentSpanId]),
        ));

        $childrenOf = function (string $parentId) use (&$childrenOf, $spans): array {
            $children = [];

            foreach ($spans as $span) {
                if ($span->parentSpanId !== $parentId || $span->spanId === $parentId) {
                    continue;
                }

                $children[] = [
                    'span' => $span,
                    'children' => $childrenOf($span->spanId),
                ];
            }

            return $children;
        };

        return array_map(
            fn (TraceSpan $root): array => [
                'span' => $root,
                'children' => $childrenOf($root->spanId),
            ],
            $roots,
        );
    }

    /**
     * @param  Collection<int, TracingSpan>  $spans
     * @return array<string, IntegrationLog>
     */
    private function integrationLogsByCorrelation(Collection $spans): array
    {
        $correlationIds = $spans
            ->filter(fn (TracingSpan $span): bool => $span->kind === SpanKind::KIND_CLIENT && $span->correlation_id !== null)
            ->map(fn (TracingSpan $span): string => (string) $span->correlation_id)
            ->unique()
            ->values();

        if ($correlationIds->isEmpty()) {
            return [];
        }

        $logs = IntegrationLog::query()
            ->whereIn('correlation_id', $correlationIds)
            ->orderByDesc('started_at')
            ->get();

        return $logs->keyBy('correlation_id')->all();
    }

    /**
     * @param  array<string, IntegrationLog>  $logsByCorrelation
     */
    private function integrationLogUrl(TracingSpan $span, array $logsByCorrelation): ?string
    {
        if ($span->kind !== SpanKind::KIND_CLIENT || $span->correlation_id === null) {
            return null;
        }

        $log = $logsByCorrelation[$span->correlation_id] ?? null;

        if ($log === null) {
            return null;
        }

        return IntegrationLogResource::getUrl('view', ['record' => $log]);
    }
}
