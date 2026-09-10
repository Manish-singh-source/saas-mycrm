<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;

final class UpdateIntegrationProviderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:80'],
            'auth_type' => ['sometimes', 'string', 'max:80'],
            'status' => ['sometimes', 'in:active,inactive'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->containsSecretKey($this->input('metadata', []))) {
                $validator->errors()->add('metadata', 'Metadata must not contain credentials or secrets.');
            }
        });
    }

    private function containsSecretKey(mixed $value): bool
    {
        if (! is_array($value)) return false;
        foreach ($value as $key => $child) {
            if (preg_match('/(secret|password|token|credential|private[_-]?key|api[_-]?key)/i', (string) $key)) return true;
            if ($this->containsSecretKey($child)) return true;
        }
        return false;
    }
}
