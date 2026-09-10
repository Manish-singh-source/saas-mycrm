<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformUserRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('platform_user_uuid');

        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'display_name' => ['sometimes', 'string', 'max:200'],
            'employee_code' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('platform_users', 'employee_code')->ignore($uuid, 'uuid')],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('platform_users', 'email')->ignore($uuid, 'uuid')],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20'],
            'designation' => ['sometimes', 'nullable', 'integer', 'exists:platform_designations,id'],
            'designation_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_designations,uuid'],
            'department' => ['sometimes', 'nullable', 'integer', 'exists:platform_departments,id'],
            'department_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_departments,uuid'],
            'manager_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_users,id'],
            'manager_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_users,uuid'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'locale' => ['sometimes', 'string', 'max:20'],
            'two_factor_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'profile_photo_file_id' => ['sometimes', 'nullable', 'integer', 'exists:files,id'],
            'role_uuids' => ['sometimes', 'array'],
            'role_uuids.*' => ['uuid', 'exists:platform_roles,uuid'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:platform_roles,id'],
            'team_uuids' => ['sometimes', 'array'],
            'team_uuids.*' => ['uuid', 'exists:platform_teams,uuid'],
            'team_ids' => ['sometimes', 'array'],
            'team_ids.*' => ['integer', 'exists:platform_teams,id'],
        ];
    }
}
