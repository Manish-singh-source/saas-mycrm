<?php

namespace App\Http\Requests;

final class UpdatePlatformSettingsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.group' => ['required', 'string', 'max:100', 'in:general,security,billing,email,storage,queue,integration'],
            'settings.*.key' => ['required', 'string', 'max:150', 'regex:/^[a-z][a-z0-9_.-]*$/'],
            'settings.*.value' => ['nullable'],
            'settings.*.value_type' => ['sometimes', 'in:string,integer,boolean,number,json'],
            'settings.*.is_encrypted' => ['sometimes', 'boolean'],
        ];
    }
}
