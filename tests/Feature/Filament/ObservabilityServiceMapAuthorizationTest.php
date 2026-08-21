<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Tracing\Models\TracingSpan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityServiceMapAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_service_map(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/observability/service-map')
            ->assertOk();
    }

    public function test_permission_granted_user_can_access_service_map(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:ServiceMap', 'web'));

        $this->actingAs($user)
            ->get('/admin/observability/service-map')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_service_map(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/observability/service-map')
            ->assertForbidden();
    }

    public function test_service_map_renders_nodes_and_metrics(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $parent = $this->seedSpan('checkout');
        $this->seedSpan('payment.charge', (string) $parent->span_id);

        $html = $this->actingAs($user)
            ->get('/admin/observability/service-map')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('checkout', $html);
        $this->assertStringContainsString('payment.charge', $html);
    }

    public function test_service_map_shows_empty_state(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $html = $this->actingAs($user)
            ->get('/admin/observability/service-map')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tidak ada data trace dalam rentang waktu terpilih', $html);
    }

    private function seedSpan(string $operation, ?string $parentSpanId = null): TracingSpan
    {
        $nowNs = (int) (now()->getTimestamp() * 1_000_000_000);

        return TracingSpan::query()->forceCreate([
            'trace_id' => Str::random(32),
            'span_id' => Str::random(16),
            'parent_span_id' => $parentSpanId,
            'name' => $operation,
            'kind' => 1,
            'status_code' => 'OK',
            'start_ns' => $nowNs,
            'end_ns' => $nowNs + 100_000_000,
            'duration_ms' => 100,
            'attributes' => [],
            'correlation_id' => null,
            'operation' => $operation,
        ]);
    }
}
