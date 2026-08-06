<?php

namespace App\Policies\Shared;

use Illuminate\Contracts\Auth\Authenticatable;

abstract class BasePolicy
{
    protected function isAuthenticated(?Authenticatable $user): bool
    {
        return $user !== null;
    }
}
