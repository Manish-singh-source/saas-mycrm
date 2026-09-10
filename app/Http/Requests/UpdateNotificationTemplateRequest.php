<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdateNotificationTemplateRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'channel' => ['sometimes', Rule::in(['email', 'sms', 'in_app', 'push', 'webhook'])],
            'subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:100000'],
            'variables' => ['sometimes', 'nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
