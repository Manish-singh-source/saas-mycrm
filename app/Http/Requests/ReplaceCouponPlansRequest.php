<?php

namespace App\Http\Requests;

final class ReplaceCouponPlansRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_uuids' => ['required', 'array'],
            'plan_uuids.*' => ['uuid', 'distinct'],
        ];
    }
}
