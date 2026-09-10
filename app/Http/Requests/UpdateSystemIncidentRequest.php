<?php

namespace App\Http\Requests;

final class UpdateSystemIncidentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'severity' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'max:50'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'resolved_at' => ['sometimes', 'nullable', 'date'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'resolution_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
