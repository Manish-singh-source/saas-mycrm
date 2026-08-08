<?php

namespace App\Models\Concerns;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

trait HasPlatformRbac
{
    public function platformRoles(): BelongsToMany
    {
        return $this->belongsToMany(PlatformRole::class, 'platform_model_has_roles', 'model_id', 'role_id')
            ->wherePivot('model_type', static::class);
    }

    public function platformDirectPermissions(): BelongsToMany
    {
        return $this->belongsToMany(PlatformPermission::class, 'platform_model_has_permissions', 'model_id', 'permission_id')
            ->wherePivot('model_type', static::class);
    }

    public function hasPlatformPermission(string $permission): bool
    {
        if ($this->tokenCan('platform:*')) {
            return true;
        }

        $direct = DB::table('platform_model_has_permissions')
            ->join('platform_permissions', 'platform_permissions.id', '=', 'platform_model_has_permissions.permission_id')
            ->where('platform_model_has_permissions.model_type', static::class)
            ->where('platform_model_has_permissions.model_id', $this->getKey())
            ->where('platform_permissions.name', $permission)
            ->where('platform_permissions.status', 'active')
            ->exists();

        if ($direct) {
            return true;
        }

        return DB::table('platform_model_has_roles')
            ->join('platform_roles', 'platform_roles.id', '=', 'platform_model_has_roles.role_id')
            ->join('platform_role_has_permissions', 'platform_role_has_permissions.role_id', '=', 'platform_roles.id')
            ->join('platform_permissions', 'platform_permissions.id', '=', 'platform_role_has_permissions.permission_id')
            ->where('platform_model_has_roles.model_type', static::class)
            ->where('platform_model_has_roles.model_id', $this->getKey())
            ->where('platform_roles.status', 'active')
            ->where('platform_permissions.status', 'active')
            ->where('platform_permissions.name', $permission)
            ->exists();
    }

    public function platformPermissionNames(): array
    {
        $rolePermissions = DB::table('platform_model_has_roles')
            ->join('platform_roles', 'platform_roles.id', '=', 'platform_model_has_roles.role_id')
            ->join('platform_role_has_permissions', 'platform_role_has_permissions.role_id', '=', 'platform_roles.id')
            ->join('platform_permissions', 'platform_permissions.id', '=', 'platform_role_has_permissions.permission_id')
            ->where('platform_model_has_roles.model_type', static::class)
            ->where('platform_model_has_roles.model_id', $this->getKey())
            ->where('platform_roles.status', 'active')
            ->where('platform_permissions.status', 'active')
            ->pluck('platform_permissions.name');

        $directPermissions = DB::table('platform_model_has_permissions')
            ->join('platform_permissions', 'platform_permissions.id', '=', 'platform_model_has_permissions.permission_id')
            ->where('platform_model_has_permissions.model_type', static::class)
            ->where('platform_model_has_permissions.model_id', $this->getKey())
            ->where('platform_permissions.status', 'active')
            ->pluck('platform_permissions.name');

        return $rolePermissions->merge($directPermissions)->unique()->values()->all();
    }
}
