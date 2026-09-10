<?php

namespace App\Http\Requests;

final class BulkDeletePlatformModulesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'module_uuids' => ['required', 'array', 'min:1'],
            'module_uuids.*' => ['uuid', 'exists:modules,uuid'],
        ];
    }
}
