<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformDesignationRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('designation_uuid');

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80', Rule::unique('platform_designations', 'code')->ignore($uuid, 'uuid')],
            'description' => ['sometimes', 'nullable', 'string'],
            'level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
