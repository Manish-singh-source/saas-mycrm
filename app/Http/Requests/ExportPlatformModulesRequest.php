<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ExportPlatformModulesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'format' => ['sometimes', Rule::in(['csv'])],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', 'string', 'max:50'],
            'filters.category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
