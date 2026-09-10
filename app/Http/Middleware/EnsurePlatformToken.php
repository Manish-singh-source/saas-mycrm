<?php

namespace App\Http\Middleware;

use App\Models\PlatformUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof PlatformUser) {
            return response()->json(['success' => false, 'message' => 'A platform account is required.', 'data' => null, 'code' => 'PLATFORM_ACCOUNT_REQUIRED'], 403);
        }
        return $next($request);
    }
}
