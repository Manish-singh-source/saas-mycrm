<?php

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use App\Models\User;
use App\Services\Shared\AuthAuditService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantId = $request->attributes->get('tenant_id');

        if (! $user instanceof User) {
            app(AuthAuditService::class)->log($request, 'suspicious_tenant_token_access', 'warning', platformUser: $user instanceof PlatformUser ? $user : null);

            return ApiResponse::businessError('Tenant token required.', 'TENANT_TOKEN_REQUIRED', Response::HTTP_UNAUTHORIZED);
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            app(AuthAuditService::class)->log($request, 'suspicious_tenant_token_mismatch', 'warning', tenantUser: $user, metadata: ['requested_tenant_id' => $tenantId]);

            return ApiResponse::businessError('Token does not belong to this tenant.', 'TENANT_TOKEN_MISMATCH', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}