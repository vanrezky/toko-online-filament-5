<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserPasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_fields_are_hidden_on_edit_and_visible_on_create(): void
    {
        $admin = User::factory()->create(['is_super_user' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldHidden('password')
            ->assertFormFieldHidden('confirm_password')
            ->assertActionExists('change_password');

        Livewire::test(CreateUser::class)
            ->assertFormFieldVisible('password')
            ->assertFormFieldVisible('confirm_password');
    }

    public function test_administrator_can_change_a_users_password_from_the_dedicated_action(): void
    {
        $admin = User::factory()->create(['is_super_user' => true]);
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('change_password', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertHasNoActionErrors();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
