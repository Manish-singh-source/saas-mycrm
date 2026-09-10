<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformDepartmentRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('department_uuid');

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => ['sometimes', 'string', 'max:80', Rule::unique('platform_departments', 'code')->ignore($uuid, 'uuid')],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_departments,id'],
            'parent_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_departments,uuid'],
            'platform_manager_user_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_users,id'],
            'manager_platform_user_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_users,uuid'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
