<?php

namespace App\Policies;

class RolePolicy extends BaseShieldPolicy
{
    protected string $subject = 'Role';
}
