<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformDepartmentRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80', 'unique:platform_departments,code'],
            'parent_id' => ['nullable', 'integer', 'exists:platform_departments,id'],
            'parent_uuid' => ['nullable', 'uuid', 'exists:platform_departments,uuid'],
            'platform_manager_user_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'manager_platform_user_uuid' => ['nullable', 'uuid', 'exists:platform_users,uuid'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
