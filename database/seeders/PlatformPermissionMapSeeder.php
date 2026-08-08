<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformPermissionMapSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        foreach ($this->permissions() as $permission) {
            $this->seedRecord('platform_permissions', ['name' => $permission, 'guard_name' => 'platform'], [
                'module' => explode('.', $permission)[0],
                'display_name' => Str::headline(str_replace('.', ' ', $permission)),
                'description' => Str::headline(str_replace('.', ' ', $permission)),
                'is_system' => true,
                'status' => 'active',
            ], true);
        }
    }

    private function permissions(): array
    {
        return [
            'dashboard.view',
            'platform_user.view', 'platform_user.create', 'platform_user.edit', 'platform_user.delete', 'platform_user.suspend',
            'platform_role.view', 'platform_role.create', 'platform_role.edit', 'platform_role.delete',
            'platform_permission.view', 'platform_permission.create', 'platform_permission.edit', 'platform_permission.delete',
            'platform_team.view', 'platform_team.create', 'platform_team.edit', 'platform_team.delete', 'platform_team.assign',
            'tenant.view', 'tenant.create', 'tenant.edit', 'tenant.suspend', 'tenant.activate', 'tenant.delete', 'tenant.impersonate',            'subscription.view', 'subscription.create', 'subscription.edit', 'subscription.upgrade', 'subscription.downgrade', 'subscription.renew', 'subscription.cancel',
            'plan.view', 'plan.create', 'plan.edit', 'plan.delete', 'feature.view', 'feature.create', 'feature.edit', 'feature.delete',
            'billing.invoice.view', 'billing.invoice.create', 'billing.invoice.edit', 'billing.invoice.send', 'billing.invoice.cancel',
            'billing.payment.view', 'billing.payment.create', 'billing.payment.refund',
            'coupon.view', 'coupon.create', 'coupon.edit', 'coupon.delete', 'module.view', 'module.edit',            'support.ticket.view', 'support.ticket.reply', 'support.ticket.assign', 'support.ticket.close',
            'support.knowledge_base.view', 'support.knowledge_base.create', 'support.knowledge_base.edit', 'support.knowledge_base.publish',
            'monitoring.view', 'monitoring.manage', 'integration.view', 'integration.create', 'integration.edit', 'integration.delete', 'integration.test',
            'setting.view', 'setting.edit', 'audit_log.view', 'audit_log.export', 'report.view', 'report.export',
        ];
    }
}