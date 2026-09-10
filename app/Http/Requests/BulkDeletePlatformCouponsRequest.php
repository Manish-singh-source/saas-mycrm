<?php

namespace App\Http\Requests;

final class BulkDeletePlatformCouponsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['coupon_uuids' => ['required', 'array', 'min:1'], 'coupon_uuids.*' => ['uuid', 'exists:coupons,uuid']];
    }
}
