<?php

namespace App\Http\Requests;

final class RotatePlatformApiTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['expires_at' => ['sometimes', 'nullable', 'date', 'after:now']];
    }
}
