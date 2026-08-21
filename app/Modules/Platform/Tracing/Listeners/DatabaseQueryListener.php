<?php

namespace App\Modules\Platform\Tracing\Listeners;

use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use Illuminate\Database\Events\QueryExecuted;
use OpenTelemetry\API\Trace\SpanKind;
use Throwable;

/**
 * Creates a child span per executed database query while tracing is enabled.
 *
 * Only the query type, affected table, and duration are recorded. The raw SQL
 * text and bound parameter values are never captured.
 */
final class DatabaseQueryListener
{
    public function __construct(private readonly TraceManager $traceManager) {}

    public function handle(QueryExecuted $event): void
    {
        if (! $this->traceManager->sourceEnabled('database')) {
            return;
        }

        $operation = strtoupper((string) preg_replace('/\s.*$/', '', (string) $event->sql));

        $attributes = [
            TracingAttributes::DB_SYSTEM => 'mysql',
            TracingAttributes::DB_OPERATION => $operation,
            TracingAttributes::DB_DURATION_MS => (int) round($event->time),
        ];

        $table = $this->parseTable((string) $event->sql);
        if ($table !== null) {
            $attributes[TracingAttributes::DB_NAMESPACE] = $table;

            // Never instrument the persistence of spans themselves. Writing a
            // span fires another QueryExecuted event; instrumenting it would
            // cause a new span, which would be persisted, and so on forever.
            if (strtolower($table) === 'tracing_spans') {
                return;
            }
        }

        $activeSpan = $this->traceManager->startSpan('DB '.($operation !== '' ? $operation : 'query'), SpanKind::KIND_CLIENT, $attributes);

        $this->traceManager->endSpan($activeSpan, []);
    }

    /**
     * Extract the first table name referenced by a query, or null when it
     * cannot be parsed. Never exposes the SQL text.
     */
    private function parseTable(string $sql): ?string
    {
        try {
            if (preg_match('/\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|FROM|JOIN)\s+`?([a-zA-Z0-9_.]+)`?/i', $sql, $matches) === 1) {
                return $matches[1];
            }
        } catch (Throwable) {
            // Parsing is best-effort; failing to parse never breaks tracing.
        }

        return null;
    }
}
