<?php

namespace App\Modules\Platform\Tracing\Services;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use Throwable;

/**
 * Read-only access to the active trace context. Safe to call anywhere: it
 * never throws and returns null when tracing is disabled or inactive.
 */
final class TraceContext
{
    public function __construct(private readonly TraceManager $manager) {}

    /**
     * The current span, or null when tracing is disabled or no span is active.
     */
    public function currentSpan(): ?SpanInterface
    {
        try {
            $span = Span::getCurrent();

            return $span->getContext()->isValid() ? $span : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The current trace ID as hex, or null when tracing is inactive.
     */
    public function traceId(): ?string
    {
        $span = $this->currentSpan();

        if ($span === null) {
            return null;
        }

        try {
            $id = $span->getContext()->getTraceId();

            return $id === '' ? null : $id;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The current tracer, or null when tracing is disabled.
     */
    public function tracer(): ?TracerInterface
    {
        return $this->manager->tracer();
    }
}
