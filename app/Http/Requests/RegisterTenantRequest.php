<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use App\Support\PaymentLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class RegisterTenantRequest extends TenantWriteRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->has('payment')) {
            PaymentLogger::failure($this, 'request_validation_failed', ['request' => $this->all(), 'errors' => $validator->errors()->toArray()]);
        }
        throw new HttpResponseException(ApiResponse::validation($validator->errors()->toArray()));
    }
}
