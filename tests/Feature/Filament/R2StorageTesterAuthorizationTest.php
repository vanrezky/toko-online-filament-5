<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class R2StorageTesterAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superuser_can_access_the_r2_tester(): void
    {
        $user = User::factory()->create(['is_super_user' => true]);

        $this->actingAs($user)
            ->get('/admin/r2-storage-tester')
            ->assertOk()
            ->assertSee('Tester Cloudflare R2');
    }

    public function test_permission_granted_user_can_access_the_r2_tester(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);
        $user->givePermissionTo(Permission::findOrCreate('View:R2StorageTester', 'web'));

        $this->actingAs($user)
            ->get('/admin/r2-storage-tester')
            ->assertOk()
            ->assertSee('Tester Cloudflare R2');
    }

    public function test_unauthorized_user_cannot_access_the_r2_tester(): void
    {
        $user = User::factory()->create(['is_super_user' => false]);

        $this->actingAs($user)
            ->get('/admin/r2-storage-tester')
            ->assertForbidden();
    }
}
