<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if (! $tenant) return ApiResponse::error('Tenant context is required.', 400, null, 'TENANT_CONTEXT_REQUIRED');
        if (! $user instanceof User || $user->status !== 'active') return ApiResponse::error('A tenant token is required.', 401, null, 'TENANT_TOKEN_REQUIRED');
        if ((int) $user->tenant_id !== (int) $tenant->id) return ApiResponse::error('Token does not belong to this tenant.', 403, null, 'TENANT_TOKEN_MISMATCH');
        if (! $token || ! $token->can('tenant:'.$tenant->uuid)) return ApiResponse::error('Token does not belong to this tenant.', 403, null, 'TENANT_TOKEN_MISMATCH');
        return $next($request);
    }
}
