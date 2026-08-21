<?php

namespace Tests\Feature\Platform\Tracing;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\Services\ServiceMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceMapServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedSpan(array $overrides = []): TracingSpan
    {
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        return TracingSpan::query()->forceCreate(array_merge([
            'trace_id' => Str::random(32),
            'span_id' => Str::random(16),
            'parent_span_id' => null,
            'name' => 'GET /checkout',
            'kind' => 1,
            'status_code' => 'OK',
            'start_ns' => $nowNs,
            'end_ns' => $nowNs + 100_000_000,
            'duration_ms' => 100,
            'attributes' => [],
            'correlation_id' => null,
            'operation' => 'checkout',
        ], $overrides));
    }

    public function test_map_derives_nodes_and_edges_from_recorded_relationships(): void
    {
        $parent = $this->seedSpan(['operation' => 'checkout']);
        $this->seedSpan([
            'parent_span_id' => (string) $parent->span_id,
            'name' => 'HTTP POST',
            'operation' => 'payment.charge',
        ]);

        $snapshot = app(ServiceMapService::class)->map('24h');

        $this->assertCount(2, $snapshot->nodes);
        $this->assertCount(1, $snapshot->edges);
        $this->assertSame('checkout', $snapshot->edges[0]->from);
        $this->assertSame('payment.charge', $snapshot->edges[0]->to);
        $this->assertSame(1, $snapshot->edges[0]->calls);
    }

    public function test_map_aggregates_request_count_error_rate_avg_and_p95(): void
    {
        $parent = $this->seedSpan(['operation' => 'checkout', 'duration_ms' => 100]);
        $this->seedSpan(['parent_span_id' => (string) $parent->span_id, 'operation' => 'checkout', 'duration_ms' => 200, 'status_code' => 'ERROR']);
        $this->seedSpan(['parent_span_id' => (string) $parent->span_id, 'operation' => 'checkout', 'duration_ms' => 300]);

        $snapshot = app(ServiceMapService::class)->map('24h');

        $node = collect($snapshot->nodes)->first(fn ($n) => $n->operation === 'checkout');

        $this->assertNotNull($node);
        $this->assertSame(3, $node->requests);
        $this->assertSame(1, $node->errors);
        $this->assertEqualsWithDelta(0.3333, $node->errorRate, 0.001);
        $this->assertEqualsWithDelta(200, $node->avgMs, 0.01);
    }

    public function test_map_p95_is_null_when_not_calculable(): void
    {
        $parent = $this->seedSpan(['operation' => 'checkout', 'duration_ms' => 100]);
        $this->seedSpan(['parent_span_id' => (string) $parent->span_id, 'operation' => 'checkout', 'duration_ms' => 200]);

        $snapshot = app(ServiceMapService::class)->map('24h');

        $node = collect($snapshot->nodes)->first(fn ($n) => $n->operation === 'checkout');

        $this->assertNotNull($node);
        $this->assertNull($node->p95Ms);
    }

    public function test_map_respects_time_range(): void
    {
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);
        $hourAgoNs = (int) (now()->subHour()->getTimestamp() * 1_000_000_000);
        $weekAgoNs = (int) (now()->subDays(8)->getTimestamp() * 1_000_000_000);

        $this->seedSpan(['operation' => 'checkout', 'start_ns' => $nowNs]);
        $this->seedSpan(['operation' => 'payment.charge', 'start_ns' => $hourAgoNs]);
        $this->seedSpan(['operation' => 'stale.job', 'start_ns' => $weekAgoNs]);

        $snapshot = app(ServiceMapService::class)->map('24h');

        $operations = collect($snapshot->nodes)->pluck('operation')->all();

        $this->assertContains('checkout', $operations);
        $this->assertContains('payment.charge', $operations);
        $this->assertNotContains('stale.job', $operations);
    }

    public function test_map_returns_empty_snapshot_when_no_data(): void
    {
        $snapshot = app(ServiceMapService::class)->map('24h');

        $this->assertSame([], $snapshot->nodes);
        $this->assertSame([], $snapshot->edges);
    }
}
