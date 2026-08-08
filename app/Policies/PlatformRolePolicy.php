<?php

namespace App\Policies;

use App\Models\PlatformRole;
use App\Models\PlatformUser;

class PlatformRolePolicy
{
    public function viewAny(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_role.view'); }
    public function view(PlatformUser $user, PlatformRole $role): bool { return $user->hasPlatformPermission('platform_role.view'); }
    public function create(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_role.create'); }
    public function update(PlatformUser $user, PlatformRole $role): bool { return $user->hasPlatformPermission('platform_role.edit'); }
    public function delete(PlatformUser $user, PlatformRole $role): bool { return ! $role->is_system && $user->hasPlatformPermission('platform_role.delete'); }
    public function assignPermissions(PlatformUser $user, PlatformRole $role): bool { return $user->hasPlatformPermission('platform_role.edit'); }
    public function assignUsers(PlatformUser $user, PlatformRole $role): bool { return $user->hasPlatformPermission('platform_role.edit'); }
}
