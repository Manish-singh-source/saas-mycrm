<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class BaseApiCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function with($request): array
    {
        /** @var Request $request */
        return [
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
        ];
    }
}
