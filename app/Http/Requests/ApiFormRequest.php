<?php
namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * Supply consistent, field-specific validation messages for every API request.
     * Individual requests can override this method when a flow needs wording beyond
     * the standard platform messages.
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->rules() as $attribute => $rules) {
            foreach ((array) $rules as $rule) {
                if (is_object($rule)) {
                    $label = $this->validationLabel($attribute);
                    $class = $rule::class;
                    if (str_ends_with($class, '\In')) $messages[$attribute . '.in'] = $label . ' contains an invalid value.';
                    elseif (str_ends_with($class, '\Unique')) $messages[$attribute . '.unique'] = $label . ' has already been taken.';
                    elseif (str_ends_with($class, '\Exists')) $messages[$attribute . '.exists'] = 'The selected ' . Str::lower($label) . ' could not be found.';
                    continue;
                }
                if (! is_string($rule)) continue;

                [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');
                $label = $this->validationLabel($attribute);
                $key = $attribute . '.' . $name;
                $values = $parameters !== '' ? explode(',', $parameters) : [];

                $messages[$key] = match ($name) {
                    'required' => $label . ' is required.',
                    'required_if' => $label . ' is required for the selected option.',
                    'required_unless' => $label . ' is required for the current selection.',
                    'required_with', 'required_with_all' => $label . ' is required when the related field is provided.',
                    'required_without', 'required_without_all' => $label . ' is required when the related field is not provided.',
                    'string' => $label . ' must be a text value.',
                    'integer' => $label . ' must be a whole number.',
                    'numeric' => $label . ' must be a number.',
                    'boolean' => $label . ' must be true or false.',
                    'array' => $label . ' must be a list of values.',
                    'email' => $label . ' must be a valid email address.',
                    'uuid' => $label . ' must be a valid UUID.',
                    'date', 'date_format' => $label . ' must be a valid date.',
                    'url', 'active_url' => $label . ' must be a valid URL.',
                    'json' => $label . ' must contain valid JSON.',
                    'in' => $label . ' contains an invalid value.',
                    'not_in' => $label . ' contains a value that is not allowed.',
                    'exists' => 'The selected ' . Str::lower($label) . ' could not be found.',
                    'unique' => $label . ' has already been taken.',
                    'confirmed' => $label . ' confirmation does not match.',
                    'same' => $label . ' must match the related field.',
                    'different' => $label . ' must be different from the related field.',
                    'min' => $label . ' must be at least ' . ($values[0] ?? 'the minimum') . '.',
                    'max' => $label . ' may not be greater than ' . ($values[0] ?? 'the maximum') . '.',
                    'between' => $label . ' must be between ' . ($values[0] ?? 'the minimum') . ' and ' . ($values[1] ?? 'the maximum') . '.',
                    'size' => $label . ' must be exactly ' . ($values[0] ?? 'the specified size') . '.',
                    'regex' => $label . ' has an invalid format.',
                    default => $messages[$key] ?? null,
                };

                if ($messages[$key] === null) unset($messages[$key]);
            }
        }

        return $messages;
    }

    protected function validationLabel(string $attribute): string
    {
        return Str::of($attribute)
            ->replaceMatches('/\.\d+/', '')
            ->replace(['_', '.', '-'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->title()
            ->toString();
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::validation($validator->errors()->toArray()));
    }
}
