<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ListPlatformDepartmentsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'filter' => ['nullable', 'array'],
            'filter.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'filter.parent_uuid' => ['nullable', 'uuid', 'exists:platform_departments,uuid'],
            'sort' => ['nullable', Rule::in(['name', 'code', 'status', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
