<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Success.', int $status = 200, array $meta = []): JsonResponse
    {
        $body = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta !== []) $body['meta'] = $meta;
        return response()->json($body, $status);
    }

    public static function error(string $message, int $status = 400, mixed $data = null, ?string $code = null): JsonResponse
    {
        $body = ['success' => false, 'message' => $message, 'data' => $data];
        if ($code !== null) $body['code'] = $code;
        return response()->json($body, $status);
    }

    public static function validation(array $errors): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
    }
    public static function businessError(string $message, string $code, int $status = 409, mixed $data = null): JsonResponse
    { return self::error($message, $status, $data, $code); }
}


