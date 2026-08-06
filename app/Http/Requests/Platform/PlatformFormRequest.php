<?php

namespace App\Http\Requests\Platform;

use App\Http\Requests\Shared\BaseApiFormRequest;

abstract class PlatformFormRequest extends BaseApiFormRequest
{
    /**
     * Platform requests must authorize through platform permissions/policies in concrete classes.
     */
    public function authorize(): bool
    {
        return parent::authorize();
    }
}
