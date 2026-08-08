<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasTenantPermission('staff.view'); }
    public function view(User $user, User $model): bool { return (int) $user->tenant_id === (int) $model->tenant_id && $user->hasTenantPermission('staff.view'); }
    public function create(User $user): bool { return $user->hasTenantPermission('staff.create'); }
    public function update(User $user, User $model): bool { return (int) $user->tenant_id === (int) $model->tenant_id && $user->hasTenantPermission('staff.edit'); }
    public function delete(User $user, User $model): bool { return (int) $user->tenant_id === (int) $model->tenant_id && $user->hasTenantPermission('staff.delete'); }
    public function assignRoles(User $user, User $model): bool { return (int) $user->tenant_id === (int) $model->tenant_id && $user->hasTenantPermission('role.edit'); }
}
