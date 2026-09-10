<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformCouponRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', 'unique:coupons,code'],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_redemptions' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'archived'])],
            'plan_uuids' => ['sometimes', 'array'],
            'plan_uuids.*' => ['uuid', 'exists:plans,uuid'],
            'tenant_uuids' => ['sometimes', 'array'],
            'tenant_uuids.*' => ['uuid', 'exists:tenants,uuid'],
        ];
    }
}
