<?php

namespace App\Policies;

use App\Models\User;

class BalancePolicy extends BaseShieldPolicy
{
    protected string $subject = 'Balance';

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_balance') || parent::viewAny($user);
    }

    public function view(User $user, mixed $record): bool
    {
        return $user->can('view_balance') || $this->viewAny($user) || parent::view($user, $record);
    }

    public function create(User $user): bool
    {
        return $user->can('create_balance') || parent::create($user);
    }
}
