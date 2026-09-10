<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantPermissionMapSeeder extends Seeder
{
    public function run(): void
    {
        $permissionMap = [
            'dashboard' => ['dashboard.view', 'dashboard.customize'],
            'notification' => ['notification.view', 'notification.manage'],
            'activity_log' => ['activity_log.view', 'activity_log.export'],
            'audit_log' => ['audit_log.view'],
            'role' => ['role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign_permissions'],
            'permission' => ['permission.view'],
            'team' => ['team.view', 'team.create', 'team.edit', 'team.delete', 'team.assign'],
            'staff' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'staff.import', 'staff.export', 'staff.manage_salary', 'staff.manage_bank'],
            'client' => ['client.view', 'client.create', 'client.edit', 'client.delete', 'client.import', 'client.export', 'client.merge'],
            'vendor' => ['vendor.view', 'vendor.create', 'vendor.edit', 'vendor.delete', 'vendor.import', 'vendor.export'],
            'lead' => ['lead.view', 'lead.create', 'lead.edit', 'lead.delete', 'lead.import', 'lead.export', 'lead.convert'],
            'renewal' => ['renewal.view', 'renewal.create', 'renewal.edit', 'renewal.delete', 'renewal.renew'],
            'project' => ['project.view', 'project.create', 'project.edit', 'project.delete', 'project.archive'],
            'task' => ['task.view', 'task.create', 'task.edit', 'task.delete', 'task.assign', 'task.log_time'],
            'todo' => ['todo.view', 'todo.create', 'todo.edit', 'todo.delete', 'todo.share'],
            'issue' => ['issue.view', 'issue.create', 'issue.edit', 'issue.delete', 'issue.assign', 'issue.close'],
            'calendar' => ['calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete', 'calendar.manage_team'],
            'attendance' => ['attendance.view', 'attendance.create', 'attendance.edit', 'attendance.approve', 'attendance.export'],
            'leave' => ['leave.view', 'leave.apply', 'leave.approve', 'leave.manage_balance'],
            'payroll' => ['payroll.view', 'payroll.generate', 'payroll.approve', 'payroll.manage_settings', 'payroll.export'],
            'holiday' => ['holiday.view', 'holiday.create', 'holiday.edit', 'holiday.delete'],
            'finance' => ['finance.invoice.view', 'finance.invoice.create', 'finance.invoice.edit', 'finance.invoice.send', 'finance.invoice.cancel', 'finance.payment.view', 'finance.payment.create', 'finance.payment.edit', 'finance.payment.export', 'finance.expense.view', 'finance.expense.create', 'finance.expense.edit', 'finance.expense.approve', 'finance.bank_account.view', 'finance.bank_account.create', 'finance.bank_account.edit', 'finance.bank_account.delete'],
            'document' => ['document.view', 'document.upload', 'document.edit', 'document.delete', 'document.share'],
            'report' => ['report.view', 'report.export', 'report.customize', 'report.edit'],
            'setting' => ['setting.view', 'setting.edit'],
            'profile' => ['profile.view', 'profile.edit', 'profile.security'],
        ];

        $now = now();
        $rows = [];
        foreach ($permissionMap as $module => $permissions) {
            foreach ($permissions as $name) {
                $rows[] = [
                    'uuid' => (string) Str::uuid(), 'module' => $module, 'name' => $name,
                    'display_name' => ucwords(str_replace(['.', '_'], ' ', $name)), 'guard_name' => 'tenant',
                    'description' => null, 'is_system' => true, 'status' => 'active',
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }

        DB::table('permissions')->upsert($rows, ['name', 'guard_name'], [
            'module', 'display_name', 'description', 'is_system', 'status', 'updated_at',
        ]);
    }
}
