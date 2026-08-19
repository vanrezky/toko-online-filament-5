<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_read_only_audit_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('Audit Logs');
    }

    public function test_permission_granted_user_can_access_audit_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:AuditLogs', 'web'));

        $this->actingAs($user)
            ->get('/admin/audit-logs')
            ->assertOk()
            ->assertSee('Audit Logs');
    }

    public function test_unauthorized_user_cannot_access_audit_logs(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }
}
