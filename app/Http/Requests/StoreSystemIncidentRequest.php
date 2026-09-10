<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StoreSystemIncidentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'max:50'],
            'started_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string'],
        ];
    }
}
