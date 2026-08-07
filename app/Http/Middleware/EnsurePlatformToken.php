<?php

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof PlatformUser) {
            app(AuthAuditService::class)->log($request, 'suspicious_platform_token_access', 'warning', tenantUser: $user instanceof User ? $user : null);

            return ApiResponse::businessError('Platform token required.', 'PLATFORM_TOKEN_REQUIRED', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}