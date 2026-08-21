<?php

namespace Tests\Feature\Platform\Tracing;

use App\Modules\Platform\Tracing\Models\TracingSpan;
use App\Modules\Platform\Tracing\Services\TraceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TraceServiceTest extends TestCase
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
            'attributes' => ['http.route' => '/checkout'],
            'correlation_id' => null,
            'operation' => 'checkout',
        ], $overrides));
    }

    public function test_list_orders_traces_by_start_time_descending(): void
    {
        $older = $this->seedSpan(['trace_id' => 'a'.str_repeat('0', 31)]);
        $newer = $this->seedSpan(['trace_id' => 'b'.str_repeat('0', 31)]);

        $olderStart = (int) $older->start_ns - 5_000_000_000;
        $olderEnd = $olderStart + 100_000_000;
        $newerStart = (int) $newer->start_ns + 5_000_000_000;
        $newerEnd = $newerStart + 100_000_000;

        $older->update(['start_ns' => $olderStart, 'end_ns' => $olderEnd]);
        $newer->update(['start_ns' => $newerStart, 'end_ns' => $newerEnd]);

        $traces = app(TraceService::class)->list([], 25, 1);

        $this->assertSame($newer->trace_id, $traces->items()[0]->traceId);
        $this->assertSame($older->trace_id, $traces->items()[1]->traceId);
    }

    public function test_list_paginates_results(): void
    {
        foreach (range(1, 3) as $i) {
            $this->seedSpan();
        }

        $pageOne = app(TraceService::class)->list([], 2, 1);
        $pageTwo = app(TraceService::class)->list([], 2, 2);

        $this->assertSame(2, $pageOne->count());
        $this->assertSame(1, $pageTwo->count());
        $this->assertSame(3, $pageOne->total());
    }

    public function test_list_filters_by_status(): void
    {
        $this->seedSpan(['status_code' => 'OK']);
        $failed = $this->seedSpan(['status_code' => 'ERROR']);

        $traces = app(TraceService::class)->list(['status' => 'ERROR'], 25, 1);

        $this->assertCount(1, $traces->items());
        $this->assertSame($failed->trace_id, $traces->items()[0]->traceId);
    }

    public function test_list_normalizes_legacy_status_casing(): void
    {
        $this->seedSpan(['status_code' => 'Unset']);

        $traces = app(TraceService::class)->list([], 25, 1);

        $this->assertSame(TracingSpan::STATUS_UNSET, $traces->items()[0]->status);
    }

    public function test_list_filters_by_operation(): void
    {
        $this->seedSpan(['operation' => 'checkout']);
        $wanted = $this->seedSpan(['operation' => 'payment.charge']);

        $traces = app(TraceService::class)->list(['operation' => 'payment'], 25, 1);

        $this->assertCount(1, $traces->items());
        $this->assertSame($wanted->trace_id, $traces->items()[0]->traceId);
    }

    public function test_list_filters_by_error_presence(): void
    {
        $this->seedSpan(['status_code' => 'OK']);
        $failed = $this->seedSpan(['status_code' => 'ERROR']);

        $traces = app(TraceService::class)->list(['has_errors' => true], 25, 1);

        $this->assertCount(1, $traces->items());
        $this->assertSame($failed->trace_id, $traces->items()[0]->traceId);
    }

    public function test_list_filters_by_correlation_id(): void
    {
        $correlation = (string) Str::uuid();
        $this->seedSpan(['correlation_id' => $correlation]);
        $this->seedSpan();

        $traces = app(TraceService::class)->list(['correlation_id' => $correlation], 25, 1);

        $this->assertCount(1, $traces->items());
        $this->assertSame($correlation, $traces->items()[0]->correlationId);
    }

    public function test_list_filters_by_duration(): void
    {
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        $this->seedSpan(['duration_ms' => 50, 'end_ns' => $nowNs + 50_000_000]);
        $slow = $this->seedSpan(['duration_ms' => 1500, 'end_ns' => $nowNs + 1_500_000_000]);

        $traces = app(TraceService::class)->list(['duration_min' => 1000], 25, 1);

        $this->assertCount(1, $traces->items());
        $this->assertSame($slow->trace_id, $traces->items()[0]->traceId);
    }

    public function test_list_searches_by_trace_id_and_correlation_id(): void
    {
        $correlation = (string) Str::uuid();
        $byTrace = $this->seedSpan();
        $byCorrelation = $this->seedSpan(['correlation_id' => $correlation]);

        $traceSearch = app(TraceService::class)->list(['search' => $byTrace->trace_id], 25, 1);
        $correlationSearch = app(TraceService::class)->list(['search' => $correlation], 25, 1);

        $this->assertCount(1, $traceSearch->items());
        $this->assertSame($byTrace->trace_id, $traceSearch->items()[0]->traceId);
        $this->assertCount(1, $correlationSearch->items());
        $this->assertSame($byCorrelation->trace_id, $correlationSearch->items()[0]->traceId);
    }

    public function test_resolve_returns_trace_detail_with_counts(): void
    {
        $traceId = Str::random(32);
        $rootSpanId = Str::random(16);
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        $this->seedSpan([
            'trace_id' => $traceId,
            'span_id' => $rootSpanId,
            'name' => 'GET /checkout',
            'operation' => 'checkout',
            'start_ns' => $nowNs,
            'end_ns' => $nowNs + 500_000_000,
            'duration_ms' => 500,
        ]);
        $this->seedSpan([
            'trace_id' => $traceId,
            'span_id' => Str::random(16),
            'parent_span_id' => $rootSpanId,
            'name' => 'DB SELECT',
            'operation' => 'SELECT',
            'status_code' => 'ERROR',
            'start_ns' => $nowNs + 50_000_000,
            'end_ns' => $nowNs + 100_000_000,
            'duration_ms' => 50,
        ]);

        $detail = app(TraceService::class)->resolve($traceId);

        $this->assertNotNull($detail);
        $this->assertSame(2, $detail->spanCount);
        $this->assertSame(1, $detail->errorCount);
        $this->assertSame('checkout', $detail->rootOperation);
        $this->assertSame(500, $detail->durationMs);
        $this->assertFalse($detail->isPartial);
        $this->assertCount(1, $detail->tree);
    }

    public function test_resolve_marks_partial_trace_when_span_has_no_end_time(): void
    {
        $traceId = Str::random(32);
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        $this->seedSpan([
            'trace_id' => $traceId,
            'name' => 'GET /checkout',
            'start_ns' => $nowNs,
            'end_ns' => $nowNs + 100_000_000,
            'duration_ms' => 100,
        ]);
        $this->seedSpan([
            'trace_id' => $traceId,
            'span_id' => Str::random(16),
            'parent_span_id' => $this->rootSpanId($traceId),
            'name' => 'DB SELECT',
            'start_ns' => $nowNs + 10_000_000,
            'end_ns' => null,
            'duration_ms' => null,
        ]);

        $detail = app(TraceService::class)->resolve($traceId);

        $this->assertNotNull($detail);
        $this->assertTrue($detail->isPartial);
    }

    public function test_resolve_returns_null_for_unknown_trace_id(): void
    {
        $this->assertNull(app(TraceService::class)->resolve(Str::random(32)));
    }

    private function rootSpanId(string $traceId): string
    {
        return (string) TracingSpan::query()
            ->where('trace_id', $traceId)
            ->whereNull('parent_span_id')
            ->value('span_id');
    }
}
