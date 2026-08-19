<?php

namespace App\Http\Middleware;

use App\Modules\Platform\Support\Correlation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Assign a correlation ID to every incoming request and echo it on the response.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header(Correlation::INBOUND_HEADERS[0])
            ?? $request->header(Correlation::INBOUND_HEADERS[1]);

        $correlationId = Correlation::validate($incoming) ?? (string) Str::uuid();

        Correlation::set($correlationId);

        $response = $next($request);

        foreach (Correlation::RESPONSE_HEADERS as $header) {
            $response->headers->set($header, $correlationId);
        }

        return $response;
    }
}
