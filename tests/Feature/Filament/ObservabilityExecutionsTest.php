<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ObservabilityExecutionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_executions_list(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/observability/executions')
            ->assertOk();
    }

    public function test_permission_granted_user_can_access_executions_list(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:Observability', 'web'));

        $this->actingAs($user)
            ->get('/admin/observability/executions')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_executions_list(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/observability/executions')
            ->assertForbidden();
    }

    public function test_superuser_can_access_execution_detail(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = (string) Str::uuid();

        $this->makeLog($correlation);

        $this->actingAs($user)
            ->get('/admin/observability/executions/'.$correlation)
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_execution_detail(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $correlation = (string) Str::uuid();

        $this->actingAs($user)
            ->get('/admin/observability/executions/'.$correlation)
            ->assertForbidden();
    }

    public function test_execution_detail_renders_the_timeline(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = (string) Str::uuid();

        $this->makeLog($correlation, '/v1/charge');

        $html = $this->actingAs($user)
            ->get('/admin/observability/executions/'.$correlation)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/v1/charge', $html);
        $this->assertStringContainsString('midtrans', $html);
    }

    public function test_empty_execution_detail_shows_empty_state(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = (string) Str::uuid();

        $html = $this->actingAs($user)
            ->get('/admin/observability/executions/'.$correlation)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tidak ada event berkorelasi yang tercatat', $html);
    }

    public function test_execution_list_shows_correlation_ids(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = (string) Str::uuid();

        $this->makeLog($correlation, '/v1/charge');

        $html = $this->actingAs($user)
            ->get('/admin/observability/executions')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($correlation, $html);
    }

    public function test_execution_search_resolves_a_matching_execution(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $correlation = (string) Str::uuid();

        $this->makeLog($correlation, '/v1/charge');

        $html = $this->actingAs($user)
            ->get('/admin/observability/executions?search='.$correlation)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($correlation, $html);
        $this->assertStringContainsString('/admin/observability/executions/'.$correlation, $html);
    }

    private function makeLog(string $correlation, string $endpoint = '/v1/test'): IntegrationLog
    {
        return IntegrationLog::query()->forceCreate([
            'direction' => IntegrationLog::DIRECTION_OUTBOUND,
            'provider' => 'midtrans',
            'type' => IntegrationLog::TYPE_API,
            'method' => 'GET',
            'endpoint' => $endpoint,
            'status' => IntegrationLog::STATUS_SUCCESS,
            'duration_ms' => 100,
            'correlation_id' => $correlation,
            'started_at' => now(),
        ]);
    }
}
