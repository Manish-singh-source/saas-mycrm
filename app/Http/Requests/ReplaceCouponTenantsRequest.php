<?php

namespace App\Http\Requests;

final class ReplaceCouponTenantsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_uuids' => ['required', 'array'],
            'tenant_uuids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
