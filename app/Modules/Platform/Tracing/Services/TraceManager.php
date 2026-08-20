<?php

namespace App\Modules\Platform\Tracing\Services;

use ArrayObject;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use Throwable;

/**
 * Central, guarded entry point for creating and finishing spans.
 *
 * Every method is best-effort: any SDK/extension/network error is caught and
 * reported without ever propagating into application code, so a tracing
 * failure can never break the business request or job.
 */
final class TraceManager
{
    public function __construct(
        private readonly ?TracerProviderInterface $provider,
        private readonly ?ArrayObject $memoryStorage = null,
    ) {}

    public function enabled(): bool
    {
        return $this->provider !== null;
    }

    /**
     * Whether a specific instrumentation source is enabled via configuration.
     */
    public function sourceEnabled(string $source): bool
    {
        return $this->enabled() && (bool) config("tracing.sources.{$source}", true);
    }

    public function tracer(): ?TracerInterface
    {
        if ($this->provider === null) {
            return null;
        }

        try {
            return $this->provider->getTracer(
                (string) config('tracing.service_name', 'toko-online'),
                config('tracing.service_version'),
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    /**
     * Start and activate a span as the current span. Returns null when tracing
     * is disabled or when a tracer is unavailable.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function startSpan(string $name, int $kind = SpanKind::KIND_INTERNAL, array $attributes = []): ?ActiveSpan
    {
        $tracer = $this->tracer();

        if ($tracer === null) {
            return null;
        }

        try {
            $builder = $tracer->spanBuilder($name)->setSpanKind($kind);

            foreach ($attributes as $key => $value) {
                $builder->setAttribute((string) $key, $value);
            }

            $span = $builder->startSpan();
            $scope = $span->activate();

            return new ActiveSpan($span, $scope);
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }
    }

    /**
     * End an active span, optionally setting final attributes and a status.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function endSpan(?ActiveSpan $activeSpan, array $attributes = [], ?string $status = null, ?string $statusDescription = null): void
    {
        if ($activeSpan === null) {
            return;
        }

        try {
            $span = $activeSpan->span();

            foreach ($attributes as $key => $value) {
                $span->setAttribute((string) $key, $value);
            }

            if ($status !== null && $status !== '') {
                $span->setStatus($status, $statusDescription);
            }

            $activeSpan->end();
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    /**
     * Record an exception on the active span without ending it.
     */
    public function recordException(SpanInterface $span, Throwable $throwable): void
    {
        try {
            $span->recordException($throwable);
            $span->setStatus(StatusCode::STATUS_ERROR, $throwable::class);
        } catch (Throwable $caught) {
            report($caught);
        }
    }

    /**
     * The span data collected so far by the in-memory exporter, or an empty
     * array when tracing is disabled or a non-memory exporter is in use.
     * Test-only helper; the processor is flushed first so all completed spans
     * are materialized.
     *
     * @return array<int, SpanDataInterface>
     */
    public function exportedSpans(): array
    {
        if ($this->provider === null || $this->memoryStorage === null) {
            return [];
        }

        try {
            if (method_exists($this->provider, 'forceFlush')) {
                $this->provider->forceFlush();
            }
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return array_values($this->memoryStorage->getArrayCopy());
    }
}
