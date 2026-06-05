<?php

namespace App\Policies;

class CustomerPolicy extends BaseShieldPolicy
{
    protected string $subject = 'Customer';
}
