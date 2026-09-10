<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformUserRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $emailUnique = Rule::unique('platform_users', 'email');
        if ($this->route()?->getActionMethod() === 'invite') {
            $emailUnique->withoutTrashed();
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:200'],
            'employee_code' => ['nullable', 'string', 'max:50', 'unique:platform_users,employee_code'],
            'email' => ['required', 'email', 'max:150', $emailUnique],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'designation' => ['nullable', 'integer', 'exists:platform_designations,id'],
            'designation_uuid' => ['nullable', 'uuid', 'exists:platform_designations,uuid'],
            'department' => ['nullable', 'integer', 'exists:platform_departments,id'],
            'department_uuid' => ['nullable', 'uuid', 'exists:platform_departments,uuid'],
            'manager_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'manager_uuid' => ['nullable', 'uuid', 'exists:platform_users,uuid'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:20'],
            'two_factor_enabled' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
            'profile_photo_file_id' => ['nullable', 'integer', 'exists:files,id'],
            'send_invite' => ['sometimes', 'boolean'],
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
