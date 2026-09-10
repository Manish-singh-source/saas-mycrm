<?php

namespace App\Http\Requests;

final class ImportPlatformCouponsRequest extends ApiFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['file_id' => ['nullable', 'integer', 'exists:files,id'], 'file' => ['nullable', 'file', 'mimes:csv,txt', 'max:10240']];
    }
}
