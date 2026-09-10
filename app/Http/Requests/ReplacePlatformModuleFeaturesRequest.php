<?php

namespace App\Http\Requests;

final class ReplacePlatformModuleFeaturesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'feature_uuids' => ['present', 'array'],
            'feature_uuids.*' => ['uuid', 'exists:features,uuid'],
        ];
    }
}
