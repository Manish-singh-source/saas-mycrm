<?php

namespace App\Actions\Tenant;

use App\Actions\Shared\BaseAction;

abstract class BaseTenantAction extends BaseAction
{
    protected function assertTenantIsResolved(mixed $tenant): void
    {
        abort_if($tenant === null, 400, 'Tenant context is required.');
    }
}
