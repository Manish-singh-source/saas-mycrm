<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->header('X-Tenant') ?: $request->input('tenant');
        if (! is_string($value) || trim($value) === '') return ApiResponse::error('Tenant context is required.', 400, null, 'TENANT_CONTEXT_REQUIRED');
        $value = trim($value);
        $tenant = Tenant::query()->where(function ($query) use ($value, $request): void {
            $query->where('uuid', $value)->orWhere('slug', $value)->orWhere('website', $value)
                ->orWhere('website', 'https://'.$value)->orWhere('website', 'http://'.$value);
            if ($request->getHost() !== '') $query->orWhere('website', $request->getHost())->orWhere('website', 'https://'.$request->getHost())->orWhere('website', 'http://'.$request->getHost());
        })->first();
        if (! $tenant) return ApiResponse::error('Tenant was not found.', 404, null, 'TENANT_NOT_FOUND');
        if (in_array($tenant->status, ['suspended', 'expired', 'cancelled', 'archived', 'inactive'], true)) return ApiResponse::error('Tenant is not active.', 403, null, 'TENANT_NOT_ACTIVE');
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);
        return $next($request);
    }
}
