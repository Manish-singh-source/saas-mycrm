<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantPermissionMapSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        foreach ($this->permissions() as $permission) {
            $this->seedRecord('permissions', ['name' => $permission, 'guard_name' => 'tenant'], [
                'module' => explode('.', $permission)[0],
                'description' => Str::headline(str_replace('.', ' ', $permission)),
            ], true);
        }
    }

    private function permissions(): array
    {
        return [
            'dashboard.view', 'dashboard.customize',
            'notification.view', 'notification.manage',
            'activity_log.view', 'activity_log.export',
            'role.view', 'role.create', 'role.edit', 'role.delete', 'role.assign_permissions',
            'permission.view',
            'team.view', 'team.create', 'team.edit', 'team.delete', 'team.assign',
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'staff.import', 'staff.export', 'staff.manage_salary', 'staff.manage_bank',
            'client.view', 'client.create', 'client.edit', 'client.delete', 'client.import', 'client.export', 'client.merge',
            'vendor.view', 'vendor.create', 'vendor.edit', 'vendor.delete', 'vendor.import', 'vendor.export',
            'lead.view', 'lead.create', 'lead.edit', 'lead.delete', 'lead.import', 'lead.export', 'lead.convert',
            'renewal.view', 'renewal.create', 'renewal.edit', 'renewal.delete', 'renewal.renew',
            'project.view', 'project.create', 'project.edit', 'project.delete', 'project.archive',
            'task.view', 'task.create', 'task.edit', 'task.delete', 'task.assign', 'task.log_time',
            'todo.view', 'todo.create', 'todo.edit', 'todo.delete', 'todo.share',
            'issue.view', 'issue.create', 'issue.edit', 'issue.delete', 'issue.assign', 'issue.close',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete', 'calendar.manage_team',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.approve', 'attendance.export',
            'leave.view', 'leave.apply', 'leave.approve', 'leave.manage_balance',
            'payroll.view', 'payroll.generate', 'payroll.approve', 'payroll.manage_settings', 'payroll.export',
            'holiday.view', 'holiday.create', 'holiday.edit', 'holiday.delete',
            'finance.invoice.view', 'finance.invoice.create', 'finance.invoice.edit', 'finance.invoice.send', 'finance.invoice.cancel',
            'finance.payment.view', 'finance.payment.create', 'finance.payment.edit', 'finance.payment.export',
            'finance.expense.view', 'finance.expense.create', 'finance.expense.edit', 'finance.expense.approve',
            'finance.bank_account.view', 'finance.bank_account.create', 'finance.bank_account.edit', 'finance.bank_account.delete',
            'document.view', 'document.upload', 'document.edit', 'document.delete', 'document.share',
            'report.view', 'report.export', 'report.customize',
            'setting.view', 'setting.edit',
            'profile.view', 'profile.edit', 'profile.security',
        ];
    }
}