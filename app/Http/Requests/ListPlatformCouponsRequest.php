<?php

namespace App\Http\Requests;

final class ListPlatformCouponsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,archived'],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
