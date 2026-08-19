<?php

namespace Tests\Feature\Platform\Queue;

use App\Modules\Platform\Queue\Services\QueueMonitorService;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class QueueMonitorServiceTest extends TestCase
{
    public function test_it_returns_a_running_snapshot_from_horizon_repositories(): void
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->once()->andReturn(12);
        $jobs->shouldReceive('countFailed')->once()->andReturn(2);
        $jobs->shouldReceive('countCompleted')->once()->andReturn(8291);
        $jobs->shouldReceive('getFailed')->once()->andReturn(collect());

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->once()->andReturn([(object) ['status' => 'running']]);

        $workloads = Mockery::mock(WorkloadRepository::class);
        $workloads->shouldReceive('get')->once()->andReturn([
            ['name' => 'high', 'length' => 3, 'wait' => 2, 'processes' => 1],
            ['name' => 'default', 'length' => 9, 'wait' => 0, 'processes' => 2],
        ]);

        $snapshot = (new QueueMonitorService($jobs, $masters, $workloads))->snapshot();

        $this->assertSame('running', $snapshot->status);
        $this->assertSame(12, $snapshot->pendingJobs);
        $this->assertSame(2, $snapshot->failedJobs);
        $this->assertSame(8291, $snapshot->processedJobs);
        $this->assertSame(['high', 'default'], $snapshot->queues);
        $this->assertTrue($snapshot->hasWorkers());
    }

    public function test_it_reports_no_workers_when_horizon_is_reachable_without_masters(): void
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->once()->andReturn(0);
        $jobs->shouldReceive('countFailed')->once()->andReturn(0);
        $jobs->shouldReceive('countCompleted')->once()->andReturn(0);
        $jobs->shouldReceive('getFailed')->once()->andReturn(collect());

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->once()->andReturn([]);

        $workloads = Mockery::mock(WorkloadRepository::class);
        $workloads->shouldReceive('get')->once()->andReturn([]);

        $snapshot = (new QueueMonitorService($jobs, $masters, $workloads))->snapshot();

        $this->assertSame('no_workers', $snapshot->status);
        $this->assertSame(0, $snapshot->pendingJobs);
        $this->assertSame([], $snapshot->queues);
        $this->assertFalse($snapshot->hasWorkers());
    }

    public function test_it_returns_an_unavailable_snapshot_when_horizon_cannot_be_reached(): void
    {
        $jobs = Mockery::mock(JobRepository::class);
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->once()->andThrow(new RuntimeException('Redis unavailable'));
        $workloads = Mockery::mock(WorkloadRepository::class);

        $snapshot = (new QueueMonitorService($jobs, $masters, $workloads))->snapshot();

        $this->assertSame('unavailable', $snapshot->status);
        $this->assertSame(0, $snapshot->pendingJobs);
        $this->assertSame(0, $snapshot->failedJobs);
        $this->assertNull($snapshot->processedJobs);
        $this->assertSame([], $snapshot->workload);
    }

    public function test_it_extracts_correlation_ids_from_recent_failed_job_payloads(): void
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->once()->andReturn(0);
        $jobs->shouldReceive('countFailed')->once()->andReturn(1);
        $jobs->shouldReceive('countCompleted')->once()->andReturn(0);
        $jobs->shouldReceive('getFailed')->once()->andReturn(collect([
            (object) [
                'id' => 'job-1',
                'name' => 'App\Jobs\ExampleJob',
                'failed_at' => '2026-08-20 01:00:00',
                'payload' => json_encode(['displayName' => 'App\Jobs\ExampleJob', 'illuminate:log:context' => ['correlation_id' => 'trace-abc']]),
            ],
            (object) [
                'id' => 'job-2',
                'name' => 'App\Jobs\ExampleJob',
                'failed_at' => '2026-08-20 01:01:00',
                'payload' => null,
            ],
        ]));

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->once()->andReturn([(object) ['status' => 'running']]);

        $workloads = Mockery::mock(WorkloadRepository::class);
        $workloads->shouldReceive('get')->once()->andReturn([]);

        $snapshot = (new QueueMonitorService($jobs, $masters, $workloads))->snapshot();

        $this->assertCount(2, $snapshot->recentFailedJobs);
        $this->assertSame('job-2', $snapshot->recentFailedJobs[0]['id']);
        $this->assertNull($snapshot->recentFailedJobs[0]['correlation_id']);
        $this->assertSame('trace-abc', $snapshot->recentFailedJobs[1]['correlation_id']);
    }
}
