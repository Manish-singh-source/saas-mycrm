<?php
namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformTeamRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('team_uuid');

        return [
            'platform_department_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_departments,id'],
            'department_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_departments,uuid'],
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string'],
            'lead_platform_user_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_users,id'],
            'lead_platform_user_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_users,uuid'],
            'assistant_lead_platform_user_id' => ['sometimes', 'nullable', 'integer', 'exists:platform_users,id'],
            'assistant_lead_platform_user_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:platform_users,uuid'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'color' => ['sometimes', 'nullable', 'string', 'max:30'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:80'],
            'visibility' => ['sometimes', Rule::in(['internal', 'private'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
