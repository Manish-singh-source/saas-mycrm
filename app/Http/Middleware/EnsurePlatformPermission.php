<?php

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user instanceof PlatformUser) {
            return ApiResponse::businessError('Platform user required.', 'PLATFORM_USER_REQUIRED', Response::HTTP_FORBIDDEN);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPlatformPermission($permission)) {
                return $next($request);
            }
        }

        return ApiResponse::businessError('Missing platform permission.', 'PLATFORM_PERMISSION_DENIED', Response::HTTP_FORBIDDEN, ['permissions' => $permissions]);
    }
}
