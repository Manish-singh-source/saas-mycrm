<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StoreNotificationTemplateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'channel' => ['required', Rule::in(['email', 'sms', 'in_app', 'push', 'webhook'])],
            'subject' => ['nullable', 'string', 'max:255', 'required_if:channel,email'],
            'body' => ['required', 'string', 'max:100000'],
            'variables' => ['sometimes', 'nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
