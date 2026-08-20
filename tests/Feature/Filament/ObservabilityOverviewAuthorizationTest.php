<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityOverviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_observability_overview(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/observability/overview')
            ->assertOk();
    }

    public function test_permission_granted_user_can_access_observability_overview(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:Observability', 'web'));

        $this->actingAs($user)
            ->get('/admin/observability/overview')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_observability_overview(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/observability/overview')
            ->assertForbidden();
    }

    public function test_correlation_drill_down_link_uses_filters_query_key(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = 'a1ca8c52-0d84-42bc-9db4-5e561df4ba0f';

        IntegrationLog::query()->create([
            'direction' => 'outbound',
            'provider' => 'midtrans',
            'type' => 'api',
            'method' => 'GET',
            'endpoint' => '/v1/charge',
            'status' => 'failed',
            'correlation_id' => $correlation,
            'started_at' => now(),
        ]);

        $html = $this->actingAs($user)
            ->get('/admin/observability/overview')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '?filters[search][value]='.$correlation,
            $html,
        );
        $this->assertStringNotContainsString('tableFilters', $html);
    }
}
