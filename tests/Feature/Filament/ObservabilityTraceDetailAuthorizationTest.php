<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Tracing\Models\TracingSpan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityTraceDetailAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_trace_detail(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $trace = $this->seedTrace();

        $this->actingAs($user)
            ->get('/admin/observability/traces/'.$trace->trace_id)
            ->assertOk();
    }

    public function test_permission_granted_user_can_access_trace_detail(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:Traces', 'web'));
        $trace = $this->seedTrace();

        $this->actingAs($user)
            ->get('/admin/observability/traces/'.$trace->trace_id)
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_trace_detail(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $trace = $this->seedTrace();

        $this->actingAs($user)
            ->get('/admin/observability/traces/'.$trace->trace_id)
            ->assertForbidden();
    }

    public function test_trace_detail_renders_waterfall_and_span_tree(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $trace = $this->seedTrace();
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        TracingSpan::query()->forceCreate([
            'trace_id' => $trace->trace_id,
            'span_id' => Str::random(16),
            'parent_span_id' => (string) $trace->span_id,
            'name' => 'DB SELECT',
            'kind' => 2,
            'status_code' => 'OK',
            'start_ns' => $nowNs + 10_000_000,
            'end_ns' => $nowNs + 50_000_000,
            'duration_ms' => 40,
            'attributes' => ['db.operation' => 'SELECT'],
            'correlation_id' => null,
            'operation' => 'SELECT',
        ]);

        $html = $this->actingAs($user)
            ->get('/admin/observability/traces/'.$trace->trace_id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('GET /checkout', $html);
        $this->assertStringContainsString('DB SELECT', $html);
        $this->assertStringContainsString($trace->trace_id, $html);
    }

    public function test_unknown_trace_id_shows_not_found(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $html = $this->actingAs($user)
            ->get('/admin/observability/traces/'.Str::random(32))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Trace tidak ditemukan', $html);
    }

    private function seedTrace(): TracingSpan
    {
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        return TracingSpan::query()->forceCreate([
            'trace_id' => Str::random(32),
            'span_id' => Str::random(16),
            'parent_span_id' => null,
            'name' => 'GET /checkout',
            'kind' => 1,
            'status_code' => 'OK',
            'start_ns' => $nowNs,
            'end_ns' => $nowNs + 500_000_000,
            'duration_ms' => 500,
            'attributes' => ['http.route' => '/checkout'],
            'correlation_id' => null,
            'operation' => 'checkout',
        ]);
    }
}
