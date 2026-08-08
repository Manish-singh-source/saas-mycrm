<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformRoleSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $permissions = DB::table('platform_permissions')->pluck('id', 'name')->all();

        foreach ($this->roles() as $role) {
            $roleId = $this->seedRecord('platform_roles', ['name' => $role['name'], 'guard_name' => 'platform'], [
                'display_name' => Str::headline(str_replace('_', ' ', $role['name'])),
                'description' => $role['description'],
                'is_system' => true,
                'status' => 'active',
            ], true);

            foreach ($this->permissionIds($permissions, $role['permissions']) as $permissionId) {
                $this->seedPivot('platform_role_has_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    /** @return list<array{name: string, description: string, permissions: list<string>|string}> */
    private function roles(): array
    {
        return [
            ['name' => 'super_admin', 'description' => 'Full SaaS platform access.', 'permissions' => '*'],
            ['name' => 'admin', 'description' => 'Platform administration access.', 'permissions' => '*'],
            ['name' => 'billing_manager', 'description' => 'Plans, subscriptions, billing, payments, coupons, and reports.', 'permissions' => ['dashboard.view', 'tenant.view', 'subscription.', 'plan.', 'feature.', 'billing.', 'coupon.', 'report.']],
            ['name' => 'support_manager', 'description' => 'Tenant support, ticket handling, knowledge base, and remote login.', 'permissions' => ['dashboard.view', 'tenant.view', 'tenant.impersonate', 'support.', 'audit_log.view', 'report.view']],
            ['name' => 'operations_manager', 'description' => 'Operations, monitoring, integrations, settings, and audit oversight.', 'permissions' => ['dashboard.view', 'tenant.view', 'monitoring.', 'integration.', 'setting.', 'audit_log.', 'report.']],
        ];
    }

    /**
     * @param  array<string, int>  $permissions
     * @param  list<string>|string  $allowed
     * @return list<int>
     */
    private function permissionIds(array $permissions, array|string $allowed): array
    {
        if ($allowed === '*') {
            return array_values($permissions);
        }

        return array_values(array_filter($permissions, function (int $id, string $name) use ($allowed): bool {
            foreach ($allowed as $needle) {
                if ($name === $needle || str_starts_with($name, $needle)) {
                    return true;
                }
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH));
    }
}