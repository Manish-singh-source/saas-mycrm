<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function list(mixed $data, ?LengthAwarePaginator $paginator = null, string $message = 'OK', array $meta = []): JsonResponse
    {
        return ApiResponse::list($data, $paginator, $message, 200, $meta);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function businessError(string $message, string $code = 'BUSINESS_ERROR', int $status = 400, array $details = []): JsonResponse
    {
        return ApiResponse::businessError($message, $code, $status, $details);
    }
}
