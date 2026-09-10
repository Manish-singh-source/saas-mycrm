<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ListPlatformFeaturesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'array'],
            'filter.module' => ['nullable', 'string', 'max:100'],
            'filter.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['module', 'name', 'code', 'data_type', 'status', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
