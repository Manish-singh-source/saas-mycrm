<?php

namespace App\Services\Tenant;

use App\Services\Shared\BaseService;

abstract class BaseTenantService extends BaseService
{
    protected function assertTenantIsResolved(mixed $tenant): void
    {
        abort_if($tenant === null, 400, 'Tenant context is required.');
    }
}
