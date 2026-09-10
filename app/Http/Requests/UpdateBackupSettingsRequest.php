<?php

namespace App\Http\Requests;

final class UpdateBackupSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:150', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'settings.*.value' => ['nullable'],
        ];
    }
}
