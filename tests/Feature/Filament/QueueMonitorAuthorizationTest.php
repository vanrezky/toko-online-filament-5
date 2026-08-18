<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QueueMonitorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_the_queue_monitor_and_horizon_gate(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/queue-monitor')
            ->assertOk()
            ->assertSee('Queue Monitor');

        $this->assertTrue(Gate::forUser($user)->allows('viewHorizon'));
    }

    public function test_permission_granted_user_can_access_queue_monitor_and_horizon_gate(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $permission = Permission::findOrCreate('View:QueueMonitor', 'web');
        $user->givePermissionTo($permission);

        $this->actingAs($user)
            ->get('/admin/queue-monitor')
            ->assertOk()
            ->assertSee('Queue Monitor');

        $this->assertTrue(Gate::forUser($user)->allows('viewHorizon'));
    }

    public function test_unauthorized_user_cannot_access_queue_monitor_or_horizon_gate(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/queue-monitor')
            ->assertForbidden();

        $this->assertFalse(Gate::forUser($user)->allows('viewHorizon'));
    }
}
