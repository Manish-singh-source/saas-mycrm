<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformDummyRoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $roles = [
                ['platform_admin', 'Platform Admin', 'Full platform administration for testing.'],
                ['support_manager', 'Support Manager', 'Support queue and knowledge base management.'],
                ['billing_manager', 'Billing Manager', 'Subscription, invoice, payment, and coupon management.'],
                ['customer_success_manager', 'Customer Success Manager', 'Tenant onboarding and account health management.'],
                ['operations_analyst', 'Operations Analyst', 'Monitoring, audit, and reporting review.'],
                ['content_manager', 'Content Manager', 'Announcements, documents, legal content, and help articles.'],
                ['readonly_auditor', 'Read-only Auditor', 'Read-only platform review access.'],
            ];
            DB::table('platform_roles')->upsert(array_map(fn ($r) => ['uuid' => (string) Str::uuid(), 'name' => $r[0], 'display_name' => $r[1], 'guard_name' => 'platform', 'description' => $r[2], 'is_system' => false, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now], $roles), ['name', 'guard_name'], ['display_name', 'description', 'is_system', 'status', 'updated_at']);
            $roleIds = DB::table('platform_roles')->whereIn('name', array_column($roles, 0))->pluck('id', 'name')->all();
            $permissions = DB::table('platform_permissions')->where('guard_name', 'platform')->get(['id', 'name', 'module']);
            $allow = function (string $role, object $permission): bool {
                if ($role === 'platform_admin') return true;
                if ($role === 'readonly_auditor') return str_ends_with($permission->name, '.view') || in_array($permission->name, ['audit_log.export', 'report.export'], true);
                $modules = [
                    'support_manager' => ['dashboard', 'platform_user', 'tenant', 'support', 'document', 'report'],
                    'billing_manager' => ['dashboard', 'tenant', 'subscription', 'plan', 'billing', 'coupon', 'report'],
                    'customer_success_manager' => ['dashboard', 'platform_user', 'tenant', 'subscription', 'support', 'document', 'report'],
                    'operations_analyst' => ['dashboard', 'platform_user', 'platform_team', 'tenant', 'monitoring', 'integration', 'audit_log', 'report'],
                    'content_manager' => ['dashboard', 'support', 'document', 'legal_document', 'announcement'],
                ];
                return in_array($permission->module, $modules[$role] ?? [], true);
            };
            $rows = [];
            foreach (array_column($roles, 0) as $role) foreach ($permissions as $permission) if (isset($roleIds[$role]) && $allow($role, $permission)) $rows[] = ['role_id' => $roleIds[$role], 'permission_id' => $permission->id];
            DB::table('platform_role_has_permissions')->insertOrIgnore($rows);
        });
    }
}
