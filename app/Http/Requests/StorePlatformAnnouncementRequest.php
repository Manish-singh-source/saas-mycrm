<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformAnnouncementRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
            'audience' => ['sometimes', Rule::in(['all', 'platform_users', 'tenants'])],
            'status' => ['sometimes', Rule::in(['draft', 'review', 'scheduled'])],
        ];
    }
}
