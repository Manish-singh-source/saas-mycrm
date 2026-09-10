<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final class StorePlatformWebhookEndpointRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'url' => ['required', 'url:http,https', 'max:2048'], 'events' => ['required', 'array', 'min:1'], 'events.*' => ['string', 'max:120', 'regex:/^[a-z0-9][a-z0-9._:-]*$/'], 'secret' => ['nullable', 'string', 'min:16', 'max:255'], 'status' => ['sometimes', Rule::in(['active', 'inactive'])], 'tenant_uuid' => ['nullable', 'uuid', 'exists:tenants,uuid']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->unsafeUrl($this->input('url'))) $validator->errors()->add('url', 'The webhook URL is not allowed.');
        });
    }

    private function unsafeUrl(?string $url): bool
    {
        $host = strtolower((string) parse_url($url ?: '', PHP_URL_HOST));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) return true;
        return filter_var($host, FILTER_VALIDATE_IP) && (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);
    }
}
