<?php
namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final class PlatformIntegrationRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'index' => ['tenant_uuid' => ['nullable', 'uuid'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']],
            'store' => ['tenant_uuid' => ['required', 'uuid', Rule::exists('tenants', 'uuid')], 'provider_code' => ['required', 'string', 'max:120', Rule::exists('integration_providers', 'code')], 'name' => ['required', 'string', 'max:255'], 'status' => ['nullable', 'string', 'max:50'], 'credentials' => ['nullable', 'array']],
            'update' => ['name' => ['sometimes', 'string', 'max:255'], 'status' => ['sometimes', 'nullable', 'string', 'max:50']],
            'credentials' => ['credentials' => ['required', 'array']],
            'mappings' => ['mappings' => ['present', 'array'], 'mappings.*.entity_type' => ['required', 'string', 'max:100'], 'mappings.*.local_field' => ['required', 'string', 'max:255'], 'mappings.*.external_field' => ['required', 'string', 'max:255'], 'mappings.*.transform_rule' => ['nullable', 'array']],
            'webhookIndex', 'syncJobs', 'logs' => ['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'between:1,100']],
            'webhookStore' => ['integration_uuid' => ['required', 'uuid', Rule::exists('tenant_integrations', 'uuid')], 'event' => ['required', 'string', 'max:120'], 'secret' => ['nullable', 'string', 'max:500'], 'status' => ['nullable', 'string', 'max:50']],
            'webhookUpdate' => ['event' => ['sometimes', 'string', 'max:120'], 'secret' => ['nullable', 'string', 'max:500'], 'status' => ['sometimes', 'string', 'max:50']],
            default => [],
        };
    }
}