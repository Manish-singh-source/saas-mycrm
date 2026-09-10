<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class RunBackupRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return ['backup_type' => ['sometimes', Rule::in(['full', 'database'])]];
    }
}
