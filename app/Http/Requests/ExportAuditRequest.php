<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ExportAuditRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'source' => ['required', Rule::in(['activity_logs', 'security_events'])],
            'format' => ['sometimes', Rule::in(['csv'])],
            'delivery' => ['sometimes', Rule::in(['download', 'job'])],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'event' => ['nullable', 'string', 'max:120'],
            'severity' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
