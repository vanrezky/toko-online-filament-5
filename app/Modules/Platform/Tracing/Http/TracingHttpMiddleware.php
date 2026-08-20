<?php

namespace App\Modules\Platform\Tracing\Http;

use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use OpenTelemetry\API\Trace\SpanKind;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle middleware registered on the application HTTP client that creates a
 * child span per outbound request.
 *
 * Only the HTTP method, URL, response status code, and duration are recorded;
 * headers, bodies, and bound payload values are never captured.
 *
 * The trace manager is resolved lazily per request so the middleware is inert
 * whenever tracing is disabled and behaves correctly once enabled at runtime.
 */
final class TracingHttpMiddleware
{
    public static function make(): callable
    {
        return static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler) {
                $traceManager = app(TraceManager::class);

                if (! $traceManager->sourceEnabled('http_client')) {
                    return $handler($request, $options);
                }

                $startedAt = hrtime(true);
                $activeSpan = $traceManager->startSpan(
                    'HTTP '.$request->getMethod(),
                    SpanKind::KIND_CLIENT,
                    [
                        TracingAttributes::HTTP_REQUEST_METHOD => $request->getMethod(),
                        TracingAttributes::URL_FULL => (string) $request->getUri(),
                    ],
                );

                $response = $handler($request, $options);

                $response->then(function (ResponseInterface $response) use ($traceManager, $activeSpan, $startedAt): ResponseInterface {
                    $durationMs = (int) round((hrtime(true) - $startedAt) / 1e6);
                    $traceManager->endSpan($activeSpan, [
                        TracingAttributes::HTTP_RESPONSE_STATUS_CODE => $response->getStatusCode(),
                        TracingAttributes::HTTP_DURATION_MS => $durationMs,
                    ]);

                    return $response;
                }, function ($reason) use ($traceManager, $activeSpan, $startedAt) {
                    $durationMs = (int) round((hrtime(true) - $startedAt) / 1e6);
                    $traceManager->endSpan($activeSpan, [
                        TracingAttributes::HTTP_RESPONSE_STATUS_CODE => 0,
                        TracingAttributes::HTTP_DURATION_MS => $durationMs,
                    ]);

                    return $reason;
                });

                return $response;
            };
        };
    }
}
