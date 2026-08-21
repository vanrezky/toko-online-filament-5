<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CacheManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_and_clear_cache_management(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/cache-management')
            ->assertOk()
            ->assertSee('Manajemen Cache')
            ->assertSee('Bersihkan Frontend content');
    }

    public function test_view_permission_can_access_but_cannot_manage_cache(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:CacheManagement', 'web'));

        $this->actingAs($user)
            ->get('/admin/cache-management')
            ->assertOk()
            ->assertSee('Manajemen Cache');
    }

    public function test_manage_permission_can_access_cache_management(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('Manage:CacheManagement', 'web'));

        $this->actingAs($user)
            ->get('/admin/cache-management')
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_cache_management(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/cache-management')
            ->assertForbidden();
    }
}
