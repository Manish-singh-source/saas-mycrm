<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class ReviewSecurityEventRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'review_status' => ['required', Rule::in(['reviewed', 'dismissed', 'escalated'])],
            'review_notes' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
