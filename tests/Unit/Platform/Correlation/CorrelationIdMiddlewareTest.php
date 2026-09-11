<?php

namespace Tests\Unit\Platform\Correlation;

use App\Http\Middleware\CorrelationIdMiddleware;
use App\Modules\Platform\Support\Correlation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::flush();

        parent::tearDown();
    }

    public function test_sequential_requests_keep_their_own_correlation_ids(): void
    {
        $middleware = new CorrelationIdMiddleware;
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();

        $firstResponse = $middleware->handle(
            Request::create('/', 'GET', [], [], [], ['HTTP_X_CORRELATION_ID' => $first]),
            static fn (): Response => new Response('first'),
        );
        $secondResponse = $middleware->handle(
            Request::create('/', 'GET', [], [], [], ['HTTP_X_CORRELATION_ID' => $second]),
            static fn (): Response => new Response('second'),
        );

        $this->assertSame($first, $firstResponse->headers->get('X-Correlation-ID'));
        $this->assertSame($second, $secondResponse->headers->get('X-Correlation-ID'));
        $this->assertSame($second, Correlation::get());
    }
}
