<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformDesignationRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80', 'unique:platform_designations,code'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
