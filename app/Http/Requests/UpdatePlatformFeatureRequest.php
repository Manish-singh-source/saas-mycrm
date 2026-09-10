<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;


final class UpdatePlatformFeatureRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $uuid = $this->route('feature_uuid');
        return [
            'module' => ['sometimes', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:255'],
            'data_type' => ['sometimes', Rule::in(['boolean', 'integer', 'decimal', 'string', 'json'])],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'plan_uuids' => ['sometimes', 'array'],
            'plan_uuids.*' => ['uuid', 'exists:plans,uuid'],
            'plan_ids' => ['sometimes', 'array'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
            'plan_features' => ['sometimes', 'array'],
            'plan_features.*.plan_uuid' => ['required_without:plan_features.*.plan_id', 'uuid', 'exists:plans,uuid'],
            'plan_features.*.plan_id' => ['required_without:plan_features.*.plan_uuid', 'integer', 'exists:plans,id'],
            'plan_features.*.value' => ['nullable', 'string'],
            'plan_features.*.metadata' => ['nullable', 'array'],
        ];
    }
}
