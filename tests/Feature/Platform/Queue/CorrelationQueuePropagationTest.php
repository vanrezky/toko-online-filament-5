<?php

namespace Tests\Feature\Platform\Queue;

use App\Modules\Platform\Support\Correlation;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Support\CorrelationCaptureJob;
use Tests\TestCase;

class CorrelationQueuePropagationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CorrelationCaptureJob::$captured = [];
    }

    public function test_dispatched_job_restores_the_active_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);

        CorrelationCaptureJob::dispatch();

        $this->assertSame([$correlationId], CorrelationCaptureJob::$captured);
    }

    public function test_retry_redispatch_preserves_the_original_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);

        CorrelationCaptureJob::dispatch();
        CorrelationCaptureJob::dispatch();

        $this->assertSame([$correlationId, $correlationId], CorrelationCaptureJob::$captured);
    }

    public function test_replaying_the_same_job_payload_across_attempts_restores_the_same_correlation_id(): void
    {
        $correlationId = (string) Str::uuid();
        Correlation::set($correlationId);

        $rawPayload = null;
        Event::listen(JobProcessing::class, function (JobProcessing $event) use (&$rawPayload): void {
            $rawPayload = $event->job->getRawBody();
        });

        CorrelationCaptureJob::dispatch();
        CorrelationCaptureJob::$captured = [];

        $this->assertNotNull($rawPayload);
        $this->assertSame(
            $correlationId,
            unserialize(data_get(json_decode($rawPayload, true), 'illuminate:log:context.data.correlation_id')),
        );

        foreach ([1, 2] as $attempt) {
            Correlation::reset();

            $job = new SyncJob($this->app, $rawPayload, 'sync', 'default');
            event(new JobProcessing('sync', $job));
            $job->fire();

            $this->assertSame($correlationId, Correlation::get());
        }

        $this->assertSame([$correlationId, $correlationId], CorrelationCaptureJob::$captured);
    }

    public function test_consecutive_jobs_use_their_own_dispatch_context(): void
    {
        $first = (string) Str::uuid();
        Correlation::set($first);
        CorrelationCaptureJob::dispatch();

        $second = (string) Str::uuid();
        Correlation::set($second);
        CorrelationCaptureJob::dispatch();

        $this->assertSame([$first, $second], CorrelationCaptureJob::$captured);
    }

    public function test_job_can_override_context_without_affecting_the_dispatched_payload(): void
    {
        $outer = (string) Str::uuid();
        Correlation::set($outer);

        $job = new CorrelationCaptureJob(override: (string) Str::uuid());
        $job->handle();

        CorrelationCaptureJob::dispatch();

        $this->assertSame([$job->override, $outer], CorrelationCaptureJob::$captured);
    }

    public function test_console_execution_assigns_a_correlation_id_to_commands(): void
    {
        event(new CommandStarting('some:command', new StringInput(''), new BufferedOutput));

        $this->assertNotNull(Context::get('correlation_id'));
    }

    public function test_console_queue_worker_commands_do_not_anchor_a_correlation_id(): void
    {
        event(new CommandStarting('horizon:supervisor', new StringInput(''), new BufferedOutput));

        $this->assertNull(Context::get('correlation_id'));
    }
}
