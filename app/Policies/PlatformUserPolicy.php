<?php

namespace App\Policies;

use App\Models\PlatformUser;

class PlatformUserPolicy
{
    public function viewAny(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_user.view'); }
    public function view(PlatformUser $user, PlatformUser $model): bool { return $user->hasPlatformPermission('platform_user.view'); }
    public function create(PlatformUser $user): bool { return $user->hasPlatformPermission('platform_user.create'); }
    public function update(PlatformUser $user, PlatformUser $model): bool { return $user->hasPlatformPermission('platform_user.edit'); }
    public function delete(PlatformUser $user, PlatformUser $model): bool { return $user->hasPlatformPermission('platform_user.delete'); }
    public function suspend(PlatformUser $user, PlatformUser $model): bool { return $user->hasPlatformPermission('platform_user.suspend'); }
    public function assignRoles(PlatformUser $user, PlatformUser $model): bool { return $user->hasPlatformPermission('platform_user.edit'); }
}
