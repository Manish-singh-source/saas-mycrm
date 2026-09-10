<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformPermissionRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'module' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150', Rule::unique('platform_permissions')->where(fn ($q) => $q->where('guard_name', $this->input('guard_name', 'platform')))],
            'display_name' => ['nullable', 'string', 'max:150'],
            'guard_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'role_uuids' => ['sometimes', 'array'],
            'role_uuids.*' => ['uuid', 'exists:platform_roles,uuid'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:platform_roles,id'],
        ];
    }
}
