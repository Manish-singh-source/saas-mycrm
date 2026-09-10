<?php

namespace App\Http\Requests;

final class ResolveMonitoringRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['resolution_notes' => ['nullable', 'string', 'max:5000']];
    }
}
