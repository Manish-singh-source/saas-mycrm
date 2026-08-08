<?php

namespace App\Policies;

use App\Models\PlatformUser;
use App\Models\Tenant;

class TenantPolicy
{
    public function viewAny(PlatformUser $user): bool { return $user->hasPlatformPermission('tenant.view'); }
    public function view(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.view'); }
    public function create(PlatformUser $user): bool { return $user->hasPlatformPermission('tenant.create'); }
    public function update(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.edit'); }
    public function delete(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.delete'); }
    public function suspend(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.suspend'); }
    public function activate(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.activate'); }
    public function impersonate(PlatformUser $user, Tenant $tenant): bool { return $user->hasPlatformPermission('tenant.impersonate'); }
}
