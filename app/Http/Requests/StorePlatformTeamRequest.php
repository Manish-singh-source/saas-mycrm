<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformTeamRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'platform_department_id' => ['nullable', 'integer', 'exists:platform_departments,id'],
            'department_uuid' => ['nullable', 'uuid', 'exists:platform_departments,uuid'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'lead_platform_user_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'lead_platform_user_uuid' => ['nullable', 'uuid', 'exists:platform_users,uuid'],
            'assistant_lead_platform_user_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'assistant_lead_platform_user_uuid' => ['nullable', 'uuid', 'exists:platform_users,uuid'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:80'],
            'visibility' => ['sometimes', Rule::in(['internal', 'private'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
