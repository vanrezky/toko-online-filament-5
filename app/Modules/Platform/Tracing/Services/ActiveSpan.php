<?php

namespace App\Modules\Platform\Tracing\Services;

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ScopeInterface;

/**
 * A started, activated span together with the context scope that must be
 * detached before the span is ended. Ensures the SDK context is always
 * restored so a long-running worker never leaks one request's active span
 * into the next.
 */
final class ActiveSpan
{
    public function __construct(
        private readonly SpanInterface $span,
        private readonly ScopeInterface $scope,
    ) {}

    public function span(): SpanInterface
    {
        return $this->span;
    }

    public function end(): void
    {
        $this->scope->detach();
        $this->span->end();
    }
}
