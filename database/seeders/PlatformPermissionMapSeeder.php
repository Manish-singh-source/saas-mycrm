<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformPermissionMapSeeder extends Seeder
{
    public function run(): void
    {
        $permissionMap = [
            'dashboard' => ['dashboard.view'],
            'platform_user' => ['platform_user.view', 'platform_user.create', 'platform_user.edit', 'platform_user.delete', 'platform_user.suspend'],
            'platform_role' => ['platform_role.view', 'platform_role.create', 'platform_role.edit', 'platform_role.delete'],
            'platform_permission' => ['platform_permission.view', 'platform_permission.create', 'platform_permission.edit', 'platform_permission.delete'],
            'platform_team' => ['platform_team.view', 'platform_team.create', 'platform_team.edit', 'platform_team.delete', 'platform_team.assign'],
            'platform_department' => ['platform_department.view', 'platform_department.create', 'platform_department.edit', 'platform_department.delete'],
            'platform_designation' => ['platform_designation.view', 'platform_designation.create', 'platform_designation.edit', 'platform_designation.delete'],
            'tenant' => ['tenant.view', 'tenant.create', 'tenant.edit', 'tenant.suspend', 'tenant.activate', 'tenant.delete', 'tenant.impersonate'],
            'subscription' => ['subscription.view', 'subscription.create', 'subscription.edit', 'subscription.upgrade', 'subscription.downgrade', 'subscription.renew', 'subscription.cancel'],
            'plan' => ['plan.view', 'plan.create', 'plan.edit', 'plan.delete'],
            'feature' => ['feature.view', 'feature.create', 'feature.edit', 'feature.delete'],
            'billing' => ['billing.invoice.view', 'billing.invoice.create', 'billing.invoice.edit', 'billing.invoice.send', 'billing.invoice.cancel', 'billing.payment.view', 'billing.payment.create', 'billing.payment.refund'],
            'coupon' => ['coupon.view', 'coupon.create', 'coupon.edit', 'coupon.delete'],
            'module' => ['module.view', 'module.edit'],
            'support' => ['support.ticket.view', 'support.ticket.reply', 'support.ticket.assign', 'support.ticket.close', 'support.knowledge_base.view', 'support.knowledge_base.create', 'support.knowledge_base.edit', 'support.knowledge_base.publish'],
            'monitoring' => ['monitoring.view', 'monitoring.manage'],
            'integration' => ['integration.view', 'integration.create', 'integration.edit', 'integration.delete', 'integration.test'],
            'setting' => ['setting.view', 'setting.edit'],
            'audit_log' => ['audit_log.view', 'audit_log.export'],
            'report' => ['report.view', 'report.export'],
            'document' => ['document.view', 'document.upload', 'document.delete'],
            'legal_document' => ['legal_document.view', 'legal_document.create', 'legal_document.edit', 'legal_document.publish'],
            'announcement' => ['announcement.view', 'announcement.create', 'announcement.edit', 'announcement.publish', 'announcement.delete'],
            'api_token' => ['api_token.view', 'api_token.create', 'api_token.rotate', 'api_token.revoke'],
        ];

        $now = now();
        $rows = [];
        foreach ($permissionMap as $module => $permissions) {
            foreach ($permissions as $name) {
                $rows[] = [
                    'uuid' => (string) Str::uuid(), 'module' => $module, 'name' => $name,
                    'display_name' => ucwords(str_replace(['.', '_'], ' ', $name)), 'guard_name' => 'platform',
                    'description' => null, 'is_system' => true, 'status' => 'active',
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }

        DB::table('platform_permissions')->upsert($rows, ['name', 'guard_name'], [
            'module', 'display_name', 'description', 'is_system', 'status', 'updated_at',
        ]);
    }
}
