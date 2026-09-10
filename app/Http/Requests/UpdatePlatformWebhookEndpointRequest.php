<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final class UpdatePlatformWebhookEndpointRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['sometimes', 'string', 'max:255'], 'url' => ['sometimes', 'url:http,https', 'max:2048'], 'events' => ['sometimes', 'array', 'min:1'], 'events.*' => ['string', 'max:120', 'regex:/^[a-z0-9][a-z0-9._:-]*$/'], 'secret' => ['sometimes', 'nullable', 'string', 'min:16', 'max:255'], 'status' => ['sometimes', Rule::in(['active', 'inactive'])], 'tenant_uuid' => ['sometimes', 'nullable', 'uuid', 'exists:tenants,uuid']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('url')) {
                $host = strtolower((string) parse_url($this->input('url'), PHP_URL_HOST));
                if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local') || (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false)) $validator->errors()->add('url', 'The webhook URL is not allowed.');
            }
        });
    }
}
