<?php

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

trait HasTenantRbac
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', static::class)
            ->wherePivot('tenant_id', $this->tenant_id);
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'model_has_permissions', 'model_id', 'permission_id')
            ->wherePivot('model_type', static::class)
            ->wherePivot('tenant_id', $this->tenant_id);
    }

    public function hasTenantPermission(string $permission): bool
    {
        if ($this->tokenCan('tenant:*')) {
            return true;
        }

        $direct = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.tenant_id', $this->tenant_id)
            ->where('model_has_permissions.model_type', static::class)
            ->where('model_has_permissions.model_id', $this->getKey())
            ->where('permissions.name', $permission)
            ->where('permissions.status', 'active')
            ->exists();

        if ($direct) {
            return true;
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.tenant_id', $this->tenant_id)
            ->where('model_has_roles.model_type', static::class)
            ->where('model_has_roles.model_id', $this->getKey())
            ->where('roles.tenant_id', $this->tenant_id)
            ->where('roles.status', 'active')
            ->where('permissions.status', 'active')
            ->where('permissions.name', $permission)
            ->exists();
    }

    public function tenantPermissionNames(): array
    {
        $rolePermissions = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.tenant_id', $this->tenant_id)
            ->where('model_has_roles.model_type', static::class)
            ->where('model_has_roles.model_id', $this->getKey())
            ->where('roles.status', 'active')
            ->where('permissions.status', 'active')
            ->pluck('permissions.name');

        $directPermissions = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.tenant_id', $this->tenant_id)
            ->where('model_has_permissions.model_type', static::class)
            ->where('model_has_permissions.model_id', $this->getKey())
            ->where('permissions.status', 'active')
            ->pluck('permissions.name');

        return $rolePermissions->merge($directPermissions)->unique()->values()->all();
    }
}
