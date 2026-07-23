<?php

namespace App\Policies;

use App\Models\Flashsale;
use App\Models\User;

class FlashsalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_flashsale');
    }

    public function view(User $user, Flashsale $flashsale): bool
    {
        return $user->can('view_flashsale');
    }

    public function create(User $user): bool
    {
        return $user->can('create_flashsale');
    }

    public function update(User $user, Flashsale $flashsale): bool
    {
        return $user->can('update_flashsale');
    }

    public function delete(User $user, Flashsale $flashsale): bool
    {
        return $user->can('delete_flashsale');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_flashsale');
    }

    public function forceDelete(User $user, Flashsale $flashsale): bool
    {
        return $user->can('force_delete_flashsale');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_flashsale');
    }

    public function restore(User $user, Flashsale $flashsale): bool
    {
        return $user->can('restore_flashsale');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_flashsale');
    }

    public function replicate(User $user, Flashsale $flashsale): bool
    {
        return $user->can('replicate_flashsale');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_flashsale');
    }
}
