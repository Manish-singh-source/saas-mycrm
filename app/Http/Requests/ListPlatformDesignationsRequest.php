<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ListPlatformDesignationsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'filter' => ['nullable', 'array'],
            'filter.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'filter.level' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::in(['name', 'code', 'level', 'status', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
