<?php

namespace App\Http\Requests;

final class BulkDeletePlatformFeaturesRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['feature_uuids' => ['required', 'array', 'min:1'], 'feature_uuids.*' => ['uuid', 'exists:features,uuid']];
    }
}
