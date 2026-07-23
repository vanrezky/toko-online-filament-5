<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy extends BaseShieldPolicy
{
    protected string $subject = 'Page';

    public function delete(User $user, mixed $record): bool
    {
        return ! ($record instanceof Page && $record->isRequiredLegalPage())
            && parent::delete($user, $record);
    }
}
