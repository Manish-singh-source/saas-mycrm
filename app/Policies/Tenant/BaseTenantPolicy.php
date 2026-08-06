<?php

namespace App\Policies\Tenant;

use App\Policies\Shared\BasePolicy;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class BaseTenantPolicy extends BasePolicy
{
    protected function canUseTenant(?Authenticatable $user, mixed $tenant = null): bool
    {
        return $this->isAuthenticated($user) && $tenant !== null;
    }
}
