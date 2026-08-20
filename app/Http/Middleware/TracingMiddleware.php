<?php

namespace App\Http\Middleware;

use App\Modules\Platform\Support\Correlation;
use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TracingMiddleware
{
    public function __construct(private readonly TraceManager $traceManager) {}

    /**
     * Create a root span for the request, record the response status and
     * duration, and record any unhandled exception. The span is always ended
     * so an exception can never leak an active span into the next request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->traceManager->sourceEnabled('http')) {
            return $next($request);
        }

        $startedAt = hrtime(true);

        $attributes = [
            TracingAttributes::HTTP_REQUEST_METHOD => $request->method(),
            TracingAttributes::HTTP_ROUTE => $request->route()?->uri() ?? $request->path(),
        ];

        if ($correlationId = Correlation::get()) {
            $attributes[TracingAttributes::CORRELATION_ID] = $correlationId;
        }

        $activeSpan = $this->traceManager->startSpan(
            $request->method().' '.$this->routeName($request),
            SpanKind::KIND_SERVER,
            $attributes,
        );

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            if ($activeSpan !== null) {
                $this->traceManager->recordException($activeSpan->span(), $throwable);
                $this->traceManager->endSpan($activeSpan, [
                    TracingAttributes::HTTP_RESPONSE_STATUS_CODE => 500,
                    TracingAttributes::HTTP_DURATION_MS => $this->durationMs($startedAt),
                ], StatusCode::STATUS_ERROR, $throwable::class);
            }

            throw $throwable;
        }

        $this->traceManager->endSpan($activeSpan, [
            TracingAttributes::HTTP_RESPONSE_STATUS_CODE => $response->getStatusCode(),
            TracingAttributes::HTTP_DURATION_MS => $this->durationMs($startedAt),
        ]);

        return $response;
    }

    private function routeName(Request $request): string
    {
        $uri = $request->route()?->uri();

        return $uri !== null && $uri !== '' ? $uri : $request->path();
    }

    private function durationMs(int $startedAtNanos): int
    {
        return (int) round((hrtime(true) - $startedAtNanos) / 1e6);
    }
}
