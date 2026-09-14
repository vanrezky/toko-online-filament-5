<?php

namespace Tests\Unit\Platform\Tracing;

use App\Modules\Platform\Support\Correlation;
use App\Modules\Platform\Tracing\Services\TraceManager;
use ArrayObject;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use Tests\TestCase;

class TracingJobMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tracing.enabled' => true,
            'tracing.exporter' => 'memory',
            'tracing.sampler_ratio' => 1.0,
            'tracing.sources.queue' => true,
        ]);

        $this->app->instance(ArrayObject::class, new ArrayObject);
        $this->app->forgetInstance(TraceManager::class);
    }

    /** @return array<int, SpanDataInterface> */
    private function spans(): array
    {
        return app(TraceManager::class)->exportedSpans();
    }

    public function test_failed_job_closes_its_span_before_the_next_job(): void
    {
        Correlation::set((string) Str::uuid());

        try {
            Bus::dispatchSync(new FailingTracingJob);
            $this->fail('Expected the tracing test job to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('expected tracing failure', $exception->getMessage());
        }

        Bus::dispatchSync(new SuccessfulTracingJob);

        $failedSpan = $this->findSpan('Job '.FailingTracingJob::class);
        $successfulSpan = $this->findSpan('Job '.SuccessfulTracingJob::class);

        $this->assertNotNull($failedSpan);
        $this->assertNotNull($successfulSpan);
        $this->assertSame(StatusCode::STATUS_ERROR, $failedSpan->getStatus()->getCode());
        $this->assertNotSame($failedSpan->getTraceId(), $successfulSpan->getTraceId());
    }

    private function findSpan(string $name): ?SpanDataInterface
    {
        foreach ($this->spans() as $span) {
            if ($span->getName() === $name) {
                return $span;
            }
        }

        return null;
    }
}

class SuccessfulTracingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        //
    }
}

class FailingTracingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function handle(): void
    {
        throw new \RuntimeException('expected tracing failure');
    }
}
