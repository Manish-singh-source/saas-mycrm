<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        $tenantId = (int) $request->attributes->get('tenant_id');

        if (! $user instanceof User || (int) $user->tenant_id !== $tenantId) {
            return ApiResponse::businessError('Tenant user required.', 'TENANT_USER_REQUIRED', Response::HTTP_FORBIDDEN);
        }

        foreach ($permissions as $permission) {
            if ($user->hasTenantPermission($permission)) {
                return $next($request);
            }
        }

        return ApiResponse::businessError('Missing tenant permission.', 'TENANT_PERMISSION_DENIED', Response::HTTP_FORBIDDEN, ['permissions' => $permissions]);
    }
}
