<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BaseShieldPolicy
{
    use HandlesAuthorization;

    protected string $subject;

    protected function allows(User $user, string $action): bool
    {
        return $user->can("{$action}:{$this->subject}");
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'View');
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->allows($user, 'View');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'Create');
    }

    public function update(User $user, mixed $record): bool
    {
        return $this->allows($user, 'Update');
    }

    public function delete(User $user, mixed $record): bool
    {
        return $this->allows($user, 'Delete');
    }

    public function deleteAny(User $user): bool
    {
        return $this->allows($user, 'Delete');
    }

    public function forceDelete(User $user, mixed $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, mixed $record): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, mixed $record): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user, 'View');
    }
}
