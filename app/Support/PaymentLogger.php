<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PaymentLogger
{
    public static function request(Request $request, string $event, array $context = []): void
    {
        Log::info('payment.'.$event, self::context($request, $context));
    }

    public static function response(Request $request, string $event, array $context = []): void
    {
        Log::info('payment.'.$event, self::context($request, $context));
    }

    public static function failure(Request $request, string $event, array $context = []): void
    {
        Log::error('payment.'.$event, self::context($request, $context));
    }

    private static function context(Request $request, array $context): array
    {
        return [
            'request_id' => $request->attributes->get('request_id') ?? $request->header('X-Request-ID'),
            'http_method' => $request->method(),
            'path' => '/'.$request->path(),
            'platform_user_id' => $request->user()?->getTable() === 'platform_users' ? $request->user()->id : null,
            ...self::redact($context),
        ];
    }

    private static function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $sensitive = ['password', 'password_confirmation', 'token', 'access_token', 'authorization', 'secret', 'key_secret', 'api_key', 'raw_request'];
        foreach ($value as $key => $item) {
            if (in_array(strtolower((string) $key), $sensitive, true) || str_contains(strtolower((string) $key), 'secret')) {
                $value[$key] = '[REDACTED]';
            } else {
                $value[$key] = self::redact($item);
            }
        }

        return $value;
    }
}
