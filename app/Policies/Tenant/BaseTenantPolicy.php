<?php

namespace App\Policies\Tenant;

use App\Models\User;
use App\Policies\Shared\BasePolicy;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseTenantPolicy extends BasePolicy
{
    protected function canUseTenant(?Authenticatable $user, mixed $tenant = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $tenantId = is_object($tenant) ? ($tenant->id ?? null) : $tenant;

        return $tenantId === null
            ? app(TenantContext::class)->hasTenant() && (int) $user->tenant_id === (int) app(TenantContext::class)->id()
            : (int) $user->tenant_id === (int) $tenantId;
    }

    protected function ownsTenantRecord(?Authenticatable $user, object|array|null $record): bool
    {
        if (! $user instanceof User || $record === null) {
            return false;
        }

        $tenantId = match (true) {
            $record instanceof Model => $record->getAttribute('tenant_id'),
            is_array($record) => $record['tenant_id'] ?? null,
            default => $record->tenant_id ?? null,
        };

        return (int) $tenantId === (int) $user->tenant_id
            && app(TenantContext::class)->owns($record);
    }

    protected function canViewSharedTenantRecord(?Authenticatable $user, object|array|null $record): bool
    {
        if (! $user instanceof User || $record === null) {
            return false;
        }

        $tenantId = match (true) {
            $record instanceof Model => $record->getAttribute('tenant_id'),
            is_array($record) => $record['tenant_id'] ?? null,
            default => $record->tenant_id ?? null,
        };

        return $tenantId === null || $this->ownsTenantRecord($user, $record);
    }
}