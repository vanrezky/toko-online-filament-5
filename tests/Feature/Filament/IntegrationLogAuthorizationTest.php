<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Modules\Platform\Integration\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IntegrationLogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_read_only_integration_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);
        $log = $this->makeLog();

        $this->actingAs($user)
            ->get('/admin/integration-logs')
            ->assertOk()
            ->assertSee('Integration Logs');

        $this->actingAs($user)
            ->get("/admin/integration-logs/{$log->id}")
            ->assertOk()
            ->assertDontSee('Create');
    }

    public function test_permission_granted_user_can_access_integration_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:IntegrationLogs', 'web'));

        $this->actingAs($user)
            ->get('/admin/integration-logs')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_integration_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/integration-logs')
            ->assertForbidden();
    }

    private function makeLog(): IntegrationLog
    {
        return IntegrationLog::query()->create([
            'direction' => 'outbound',
            'provider' => 'apicoid',
            'type' => 'api',
            'method' => 'GET',
            'endpoint' => '/expedition/shipping-cost',
            'status' => 'failed',
            'correlation_id' => 'a1ca8c52-0d84-42bc-9db4-5e561df4ba0f',
            'request_headers' => ['authorization' => '********'],
            'request_body' => ['token' => '********'],
            'started_at' => now(),
        ]);
    }
}
