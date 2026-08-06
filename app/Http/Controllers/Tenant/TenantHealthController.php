<?php

namespace App\Http\Controllers\Tenant;

use Illuminate\Http\JsonResponse;

class TenantHealthController extends BaseTenantController
{
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'scope' => 'tenant',
            'version' => 'v1',
        ], 'Tenant API is ready.');
    }
}
