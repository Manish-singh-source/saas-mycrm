<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformPermissionRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('permission_uuid');

        return [
            'module' => ['sometimes', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:150', Rule::unique('platform_permissions', 'name')->ignore($uuid, 'uuid')->where(fn ($q) => $q->where('guard_name', $this->input('guard_name', 'platform')))],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'guard_name' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'role_uuids' => ['sometimes', 'array'],
            'role_uuids.*' => ['uuid', 'exists:platform_roles,uuid'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:platform_roles,id'],
        ];
    }
}
