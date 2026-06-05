<?php

namespace App\Policies;

class CustomerLevelPolicy extends BaseShieldPolicy
{
    protected string $subject = 'CustomerLevel';
}
