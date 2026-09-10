<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdatePlatformAnnouncementRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:100000'],
            'audience' => ['sometimes', Rule::in(['all', 'platform_users', 'tenants'])],
            'status' => ['sometimes', Rule::in(['draft', 'review', 'scheduled', 'cancelled'])],
        ];
    }
}
