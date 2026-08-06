<?php

namespace App\Http\Controllers\Platform;

use Illuminate\Http\JsonResponse;

class PlatformHealthController extends BasePlatformController
{
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'scope' => 'platform',
            'version' => 'v1',
        ], 'Platform API is ready.');
    }
}
