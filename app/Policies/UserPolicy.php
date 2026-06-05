<?php

namespace App\Policies;

class UserPolicy extends BaseShieldPolicy
{
    protected string $subject = 'User';
}
