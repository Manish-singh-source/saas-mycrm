<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StorePlatformApiTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'abilities' => ['required', 'array', 'min:1'], 'abilities.*' => ['string', 'max:150', Rule::exists('platform_permissions', 'name')->where(fn ($q) => $q->where('guard_name', 'platform')->where('status', 'active'))], 'expires_at' => ['nullable', 'date', 'after:now']];
    }
}
