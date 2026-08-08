<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool { return $user->hasTenantPermission('role.view'); }
    public function view(User $user, Role $role): bool { return (int) $user->tenant_id === (int) $role->tenant_id && $user->hasTenantPermission('role.view'); }
    public function create(User $user): bool { return $user->hasTenantPermission('role.create'); }
    public function update(User $user, Role $role): bool { return (int) $user->tenant_id === (int) $role->tenant_id && $user->hasTenantPermission('role.edit'); }
    public function delete(User $user, Role $role): bool { return (int) $user->tenant_id === (int) $role->tenant_id && ! $role->is_system && $user->hasTenantPermission('role.delete'); }
    public function assignPermissions(User $user, Role $role): bool { return (int) $user->tenant_id === (int) $role->tenant_id && $user->hasTenantPermission('role.assign_permissions'); }
    public function assignUsers(User $user, Role $role): bool { return (int) $user->tenant_id === (int) $role->tenant_id && $user->hasTenantPermission('role.edit'); }
}
