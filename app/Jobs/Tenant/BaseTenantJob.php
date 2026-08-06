<?php

namespace App\Jobs\Tenant;

use App\Jobs\Shared\BaseJob;

abstract class BaseTenantJob extends BaseJob
{
    public function __construct(protected readonly int|string $tenantId)
    {
    }
}
