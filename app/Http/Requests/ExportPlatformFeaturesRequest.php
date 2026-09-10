<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ExportPlatformFeaturesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'format' => ['sometimes', Rule::in(['csv'])],
            'filters' => ['nullable', 'array'],
            'filters.module' => ['nullable', 'string', 'max:100'],
            'filters.status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['module', 'name', 'code', 'data_type', 'status', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'columns' => ['nullable', 'array'],
            'columns.*' => [Rule::in(['uuid', 'module', 'name', 'code', 'data_type', 'unit', 'description', 'status', 'plans_count', 'created_at', 'updated_at'])],
        ];
    }
}
