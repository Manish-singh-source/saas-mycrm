<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $headers
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = [],
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => self::meta($meta),
            'errors' => null,
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $headers
     */
    public static function list(
        mixed $data,
        ?LengthAwarePaginator $paginator = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = [],
        array $headers = []
    ): JsonResponse {
        if ($paginator !== null) {
            $meta['pagination'] = [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ];
        }

        return self::success($data, $message, $status, $meta, $headers);
    }

    /**
     * @param  array<string, mixed>|MessageBag|ViewErrorBag  $errors
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $headers
     */
    public static function validationError(
        array|MessageBag|ViewErrorBag $errors,
        string $message = 'Validation failed.',
        int $status = 422,
        array $meta = [],
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => self::meta([
                'error_code' => 'VALIDATION_ERROR',
                ...$meta,
            ]),
            'errors' => [
                'details' => self::normalizeErrors($errors),
            ],
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $headers
     */
    public static function businessError(
        string $message,
        string $code = 'BUSINESS_ERROR',
        int $status = 400,
        array $errors = [],
        array $meta = [],
        array $headers = []
    ): JsonResponse {
        return self::error($message, $code, $errors, $status, $meta, $headers);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $headers
     */
    public static function error(
        string $message,
        string $code,
        array $errors = [],
        int $status = 400,
        array $meta = [],
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => self::meta($meta),
            'errors' => [
                'code' => $code,
                'details' => $errors,
            ],
        ], $status, $headers);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private static function meta(array $meta): array
    {
        return array_filter([
            'request_id' => request()->attributes->get('request_id'),
            ...$meta,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>|MessageBag|ViewErrorBag  $errors
     * @return array<string, mixed>
     */
    private static function normalizeErrors(array|MessageBag|ViewErrorBag $errors): array
    {
        if ($errors instanceof ViewErrorBag) {
            return $errors->toArray();
        }

        if ($errors instanceof MessageBag) {
            return $errors->toArray();
        }

        return Arr::wrap($errors);
    }
}
