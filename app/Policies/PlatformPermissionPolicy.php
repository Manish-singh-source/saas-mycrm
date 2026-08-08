<?php

namespace App\Policies;

use App\Models\PlatformPermission;
use App\Models\PlatformUser;

class PlatformPermissionPolicy
{
    public function viewAny(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_permission.view'); }
    public function view(PlatformUser $user, PlatformPermission $permission): bool { return $user->hasPlatformPermission('platform_permission.view'); }
    public function create(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_permission.create'); }
    public function update(PlatformUser $user, PlatformPermission $permission): bool { return $user->hasPlatformPermission('platform_permission.edit'); }
    public function delete(PlatformUser $user, PlatformPermission $permission): bool { return ! $permission->is_system && $user->hasPlatformPermission('platform_permission.delete'); }
}
