<?php

namespace App\Modules\Platform\Tracing\Queue;

use App\Modules\Platform\Support\Correlation;
use App\Modules\Platform\Tracing\Services\ActiveSpan;
use App\Modules\Platform\Tracing\Services\TraceManager;
use App\Modules\Platform\Tracing\Support\TracingAttributes;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Throwable;

/**
 * Creates a span for a queued job when a correlation ID is active and the job
 * carries it. Jobs without a correlation ID produce no span. The span is ended
 * on processed or failed, and the SDK scope is always detached so a worker
 * never leaks an active span into the next job.
 */
final class TracingJobMiddleware
{
    /** @var array<int, array{span: ActiveSpan, started_at: int}> */
    private static array $stack = [];

    public static function handleStart(JobProcessing $event): void
    {
        $traceManager = app(TraceManager::class);

        if (! $traceManager->sourceEnabled('queue')) {
            return;
        }

        $correlationId = Correlation::get();

        if ($correlationId === null) {
            return;
        }

        $name = self::jobName($event->job);

        $activeSpan = $traceManager->startSpan('Job '.$name, SpanKind::KIND_CONSUMER, [
            TracingAttributes::JOB_NAME => $name,
            TracingAttributes::CORRELATION_ID => $correlationId,
        ]);

        if ($activeSpan !== null) {
            self::$stack[] = ['span' => $activeSpan, 'started_at' => (int) hrtime(true)];
        }
    }

    public static function handleEnd(JobProcessed|JobFailed $event): void
    {
        if (self::$stack === []) {
            return;
        }

        $frame = array_pop(self::$stack);
        $traceManager = app(TraceManager::class);

        $attributes = [
            TracingAttributes::JOB_DURATION_MS => (int) round((hrtime(true) - $frame['started_at']) / 1e6),
        ];

        $status = null;
        $statusDescription = null;

        if ($event instanceof JobFailed) {
            $status = StatusCode::STATUS_ERROR;
            $statusDescription = $event->exception instanceof Throwable ? $event->exception::class : null;
        }

        $traceManager->endSpan($frame['span'], $attributes, $status, $statusDescription);
    }

    private static function jobName(Job $job): string
    {
        try {
            $name = method_exists($job, 'resolveName') ? $job->resolveName() : null;

            return is_string($name) && $name !== '' ? $name : $job::class;
        } catch (Throwable) {
            return $job::class;
        }
    }
}
