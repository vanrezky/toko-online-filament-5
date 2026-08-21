<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Tracing\Models\TracingSpan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityTracesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_traces_list(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/observability/traces')
            ->assertOk();
    }

    public function test_permission_granted_user_can_access_traces_list(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:Traces', 'web'));

        $this->actingAs($user)
            ->get('/admin/observability/traces')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_traces_list(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/observability/traces')
            ->assertForbidden();
    }

    public function test_traces_list_renders_trace_rows(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $trace = $this->seedTrace();

        $html = $this->actingAs($user)
            ->get('/admin/observability/traces')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($trace->trace_id, $html);
        $this->assertStringContainsString('checkout', $html);
    }

    public function test_traces_list_shows_empty_state(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $html = $this->actingAs($user)
            ->get('/admin/observability/traces')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tidak ada trace yang cocok dengan filter saat ini', $html);
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
            'end_ns' => $nowNs + 100_000_000,
            'duration_ms' => 100,
            'attributes' => ['http.route' => '/checkout'],
            'correlation_id' => null,
            'operation' => 'checkout',
        ]);
    }
}
