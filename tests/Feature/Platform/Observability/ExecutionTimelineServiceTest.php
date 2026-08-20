<?php

namespace Tests\Feature\Platform\Observability;

use App\Models\Product;
use App\Modules\Platform\Audit\Services\AuditLogService;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use App\Modules\Platform\Observability\Services\ExecutionTimelineService;
use App\Modules\Platform\Support\Correlation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExecutionTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_correlation_groups_events_into_one_execution(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinutes(5));
        $this->makeIntegrationLog($correlation, $now->subMinutes(3));
        $this->makeIntegrationLog($correlation, $now->subMinute());

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $this->assertSame($correlation, $timeline->correlationId);
        $this->assertCount(3, $timeline->events);
        $this->assertSame(3, $timeline->integrationCount);
        $this->assertSame(0, $timeline->auditCount);
        $this->assertSame(0, $timeline->queueCount);
    }

    public function test_different_correlations_remain_separate(): void
    {
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($first, $now->subMinute());
        $this->makeIntegrationLog($second, $now->subMinute());

        $service = app(ExecutionTimelineService::class);

        $this->assertCount(1, $service->timeline($first)->events);
        $this->assertCount(1, $service->timeline($second)->events);
    }

    public function test_timeline_is_ordered_by_occurred_at_ascending(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinutes(8), 300);
        $this->makeIntegrationLog($correlation, $now->subMinutes(5), 200);
        $this->makeIntegrationLog($correlation, $now->subMinutes(2), 100);

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $durations = collect($timeline->events)->pluck('duration_ms')->all();

        $this->assertSame([300, 200, 100], $durations);
    }

    public function test_duration_is_displayed_and_missing_duration_is_null(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinute(), 250);
        $this->makeIntegrationLog($correlation, $now->subMinutes(2), null);

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $durations = collect($timeline->events)->pluck('duration_ms')->all();

        $this->assertContains(250, $durations);
        $this->assertContains(null, $durations);
    }

    public function test_missing_status_is_kept_null(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinute(), 100);

        $product = Product::factory()->create();
        Correlation::set($correlation);
        app(AuditLogService::class)->logBusinessAction('audit without status', $product);
        Correlation::reset();

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $auditEvent = collect($timeline->events)->firstWhere('source', 'audit');

        $this->assertNotNull($auditEvent);
        $this->assertNull($auditEvent['status']);
    }

    public function test_failed_events_keep_their_failed_status(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinute(), 100, IntegrationLog::STATUS_FAILED);

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $this->assertSame(IntegrationLog::STATUS_FAILED, $timeline->events[0]['status']);
    }

    public function test_empty_execution_has_no_events(): void
    {
        $timeline = app(ExecutionTimelineService::class)->timeline((string) Str::uuid());

        $this->assertTrue($timeline->isEmpty());
        $this->assertSame([], $timeline->events);
    }

    public function test_audit_events_are_linked_when_correlation_is_in_properties(): void
    {
        $correlation = (string) Str::uuid();
        $product = Product::factory()->create();

        Correlation::set($correlation);
        app(AuditLogService::class)->logBusinessAction('test action', $product);
        Correlation::reset();

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $this->assertSame(1, $timeline->auditCount);
        $auditEvent = collect($timeline->events)->firstWhere('source', 'audit');
        $this->assertSame('test action', $auditEvent['title']);
        $this->assertNotNull($auditEvent['link']);
    }

    public function test_sensitive_payloads_are_not_in_the_timeline(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinute(), 100, IntegrationLog::STATUS_SUCCESS, [
            'request_headers' => ['Authorization' => 'Bearer super-secret-token'],
            'request_body' => ['api_key' => 'sk-secret-key'],
            'response_body' => ['payment_token' => 'raw-payment-token'],
        ]);

        $timeline = app(ExecutionTimelineService::class)->timeline($correlation);

        $encoded = json_encode($timeline->events) ?: '';

        $this->assertStringNotContainsString('super-secret-token', $encoded);
        $this->assertStringNotContainsString('sk-secret-key', $encoded);
        $this->assertStringNotContainsString('raw-payment-token', $encoded);
    }

    public function test_list_enumerates_distinct_correlations_most_recent_first(): void
    {
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($first, $now->subHour());
        $this->makeIntegrationLog($second, $now->subMinute());

        $executions = app(ExecutionTimelineService::class)->list();

        $this->assertCount(2, $executions);
        $this->assertSame($second, $executions[0]['correlation_id']);
        $this->assertSame($first, $executions[1]['correlation_id']);
    }

    public function test_resolve_returns_execution_for_exact_correlation(): void
    {
        $correlation = (string) Str::uuid();
        $now = CarbonImmutable::parse('2026-08-20 12:00:00', 'UTC');

        $this->makeIntegrationLog($correlation, $now->subMinute());

        $executions = app(ExecutionTimelineService::class)->resolve($correlation);

        $this->assertCount(1, $executions);
        $this->assertSame($correlation, $executions[0]['correlation_id']);
    }

    public function test_resolve_returns_empty_for_unknown_correlation(): void
    {
        $executions = app(ExecutionTimelineService::class)->resolve((string) Str::uuid());

        $this->assertSame([], $executions);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeIntegrationLog(string $correlation, CarbonImmutable $createdAt, ?int $duration = 100, ?string $status = IntegrationLog::STATUS_SUCCESS, array $extra = []): IntegrationLog
    {
        return IntegrationLog::query()->forceCreate(array_merge([
            'direction' => IntegrationLog::DIRECTION_OUTBOUND,
            'provider' => 'midtrans',
            'type' => IntegrationLog::TYPE_API,
            'method' => 'GET',
            'endpoint' => '/v1/test',
            'status' => $status,
            'duration_ms' => $duration,
            'correlation_id' => $correlation,
            'started_at' => $createdAt,
            'created_at' => $createdAt,
        ], $extra));
    }
}
