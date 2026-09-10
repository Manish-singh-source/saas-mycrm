<?php

namespace App\Http\Requests;

final class UpsertTenantModuleOverrideRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'limits' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
