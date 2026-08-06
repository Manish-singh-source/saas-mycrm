<?php

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Shared\BaseApiFormRequest;

abstract class TenantFormRequest extends BaseApiFormRequest
{
    /**
     * Tenant requests must authorize against the resolved tenant and scoped permissions.
     */
    public function authorize(): bool
    {
        return parent::authorize();
    }
}
