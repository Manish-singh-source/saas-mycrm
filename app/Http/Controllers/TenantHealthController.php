<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

final class TenantHealthController extends Controller
{
    public function __invoke(): mixed
    {
        return ApiResponse::success(['scope' => 'tenant', 'version' => 'v1'], 'Tenant API is healthy.');
    }
}
