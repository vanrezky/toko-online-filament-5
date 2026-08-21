<?php

namespace App\Modules\Platform\Tracing\SpanProcessors;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\Support\SpanAttributeFilter;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use Throwable;

/**
 * Persists ended spans to the application database in addition to any
 * configured exporter.
 *
 * Spans are inserted on start (with a null end time) and updated on end.
 * A span that starts but never ends therefore remains stored with a null
 * end time, which is how incomplete ("partial") traces are detected. Every
 * database interaction is best-effort: failures are reported and swallowed
 * so tracing can never break the business request.
 */
final class DbSpanProcessor implements SpanProcessorInterface
{
    private SpanAttributeFilter $attributeFilter;

    public function __construct(?SpanAttributeFilter $attributeFilter = null)
    {
        $this->attributeFilter = $attributeFilter ?? new SpanAttributeFilter;
    }

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        try {
            $data = $span->toSpanData();

            $attributes = $this->attributeFilter->filter($data->getAttributes()->toArray());

            TracingSpan::query()->create([
                'trace_id' => $data->getTraceId(),
                'span_id' => $data->getSpanId(),
                'parent_span_id' => $this->emptyToNull($data->getParentSpanId()),
                'name' => $data->getName(),
                'kind' => $data->getKind(),
                'start_ns' => $data->getStartEpochNanos(),
                'attributes' => $attributes,
                'correlation_id' => $attributes[TracingAttributes::CORRELATION_ID] ?? null,
                'operation' => $this->operation($data),
            ]);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    public function onEnd(ReadableSpanInterface $span): void
    {
        try {
            $data = $span->toSpanData();

            $durationNanos = max(0, $data->getEndEpochNanos() - $data->getStartEpochNanos());

            TracingSpan::query()->updateOrCreate(
                ['span_id' => $data->getSpanId()],
                [
                    'trace_id' => $data->getTraceId(),
                    'name' => $data->getName(),
                    'kind' => $data->getKind(),
                    'parent_span_id' => $this->emptyToNull($data->getParentSpanId()),
                    'start_ns' => $data->getStartEpochNanos(),
                    'end_ns' => $data->getEndEpochNanos(),
                    'duration_ms' => (int) round($durationNanos / 1_000_000),
                    'status_code' => strtoupper($data->getStatus()->getCode()),
                    'status_description' => $this->emptyToNull($data->getStatus()->getDescription()),
                    'events' => $this->sanitizedEvents($data),
                ],
            );
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return true;
    }

    /**
     * @return array<int, array{name: string, epoch_ns: int, attributes: array<string, mixed>}>
     */
    private function sanitizedEvents(SpanDataInterface $data): array
    {
        $events = [];

        foreach ($data->getEvents() as $event) {
            $events[] = [
                'name' => $event->getName(),
                'epoch_ns' => $event->getEpochNanos(),
                'attributes' => $this->attributeFilter->filter($event->getAttributes()->toArray()),
            ];
        }

        return $events;
    }

    private function operation(SpanDataInterface $data): ?string
    {
        $attributes = $data->getAttributes()->toArray();

        foreach ([
            TracingAttributes::HTTP_ROUTE,
            TracingAttributes::JOB_NAME,
            TracingAttributes::DB_OPERATION,
        ] as $key) {
            if (isset($attributes[$key]) && $attributes[$key] !== null && $attributes[$key] !== '') {
                return (string) $attributes[$key];
            }
        }

        return $data->getName();
    }

    private function emptyToNull(string $value): ?string
    {
        if ($value === '' || $value === '0000000000000000') {
            return null;
        }

        return $value;
    }
}
