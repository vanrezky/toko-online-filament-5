<?php

namespace App\Policies;

class TransactionPolicy extends BaseShieldPolicy
{
    protected string $subject = 'Transaction';
}
