<?php

namespace App\Policies\Platform;

use App\Policies\Shared\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class BasePlatformPolicy extends BasePolicy
{
    protected function canUsePlatform(?Authenticatable $user): bool
    {
        return $this->isAuthenticated($user);
    }
}
