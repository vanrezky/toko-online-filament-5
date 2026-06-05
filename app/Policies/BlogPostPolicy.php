<?php

namespace App\Policies;

class BlogPostPolicy extends BaseShieldPolicy
{
    protected string $subject = 'BlogPost';
}
