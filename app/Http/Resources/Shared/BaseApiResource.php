<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function baseMeta(Request $request): array
    {
        return [
            'request_id' => $request->attributes->get('request_id'),
        ];
    }
}
