<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->store($request, 500, $startedAt);
            throw $exception;
        }

        $this->store($request, $response->getStatusCode(), $startedAt);

        return $response;
    }

    private function store(Request $request, int $statusCode, float $startedAt): void
    {
        if (! str_starts_with($request->path(), 'api/platform/v1/')) {
            return;
        }

        try {
            $user = $request->user();
            $isTenantUser = $user && $user->getTable() === 'users';

            ApiRequestLog::query()->create([
                'tenant_id' => $isTenantUser ? $user->tenant_id : null,
                'user_id' => $isTenantUser ? $user->getKey() : null,
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'status_code' => $statusCode,
                'duration_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Monitoring must never make an otherwise successful API request fail.
        }
    }
}
