<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\ApiResponse;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $value = $request->header('X-Tenant') ?: $request->input('tenant');

        if (! is_string($value) || trim($value) === '') {
            return ApiResponse::businessError('Tenant context is required.', 'TENANT_CONTEXT_REQUIRED', Response::HTTP_BAD_REQUEST);
        }

        $tenant = $this->resolveTenant(trim($value), $request->getHost());

        if ($tenant === null) {
            return ApiResponse::businessError('Tenant was not found.', 'TENANT_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        if (in_array($tenant->status, ['suspended', 'expired', 'cancelled', 'archived'], true)) {
            return ApiResponse::businessError('Tenant is not active.', 'TENANT_NOT_ACTIVE', Response::HTTP_FORBIDDEN);
        }

        app(TenantContext::class)->set($tenant);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_context', app(TenantContext::class));

        return $next($request);
    }

    private function resolveTenant(string $value, string $host): ?Tenant
    {
        return Tenant::query()
            ->where(function ($query) use ($value, $host): void {
                $query->where('slug', $value)
                    ->orWhere('uuid', $value)
                    ->orWhere('website', $value)
                    ->orWhere('website', 'https://'.$value)
                    ->orWhere('website', 'http://'.$value);

                if ($host !== '') {
                    $query->orWhere('website', $host)
                        ->orWhere('website', 'https://'.$host)
                        ->orWhere('website', 'http://'.$host);
                }
            })
            ->first();
    }
}