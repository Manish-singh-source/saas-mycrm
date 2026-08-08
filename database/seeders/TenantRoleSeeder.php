<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantRoleSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $permissions = DB::table('permissions')->pluck('id', 'name')->all();
        $tenants = DB::table('tenants')->select('id')->get();

        foreach ($tenants as $tenant) {
            foreach ($this->roles() as $role) {
                $roleId = $this->seedRecord('roles', ['tenant_id' => $tenant->id, 'name' => $role['name'], 'guard_name' => 'tenant'], [
                    'display_name' => Str::headline(str_replace('_', ' ', $role['name'])),
                    'description' => $role['description'],
                    'is_system' => true,
                    'status' => 'active',
                ], true);

                foreach ($this->permissionIds($permissions, $role['permissions']) as $permissionId) {
                    $this->seedPivot('role_has_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
            }
        }
    }

    /** @return list<array{name: string, description: string, permissions: list<string>|string}> */
    private function roles(): array
    {
        return [
            ['name' => 'owner', 'description' => 'Full tenant ownership access.', 'permissions' => '*'],
            ['name' => 'admin', 'description' => 'Tenant administration access.', 'permissions' => '*'],
            ['name' => 'manager', 'description' => 'Management access across CRM and operations.', 'permissions' => ['dashboard.', 'notification.view', 'activity_log.view', 'team.view', 'staff.view', 'client.', 'vendor.view', 'lead.', 'project.', 'task.', 'issue.', 'calendar.', 'renewal.', 'report.view']],
            ['name' => 'staff', 'description' => 'General staff self-service and task access.', 'permissions' => ['dashboard.view', 'notification.view', 'profile.', 'task.view', 'task.edit', 'task.log_time', 'todo.', 'calendar.view', 'attendance.view', 'leave.apply', 'document.view']],
            ['name' => 'accountant', 'description' => 'Finance, invoices, payments, expenses, and reports.', 'permissions' => ['dashboard.view', 'finance.', 'client.view', 'vendor.view', 'project.view', 'document.view', 'report.']],
            ['name' => 'hr_manager', 'description' => 'Staff, attendance, leave, payroll, holidays, and HR settings.', 'permissions' => ['dashboard.view', 'staff.', 'attendance.', 'leave.', 'payroll.', 'holiday.', 'team.view', 'setting.view', 'report.']],
            ['name' => 'project_manager', 'description' => 'Projects, tasks, time logs, issues, teams, and project reports.', 'permissions' => ['dashboard.view', 'project.', 'task.', 'issue.', 'team.view', 'staff.view', 'client.view', 'document.', 'report.view']],
            ['name' => 'sales_user', 'description' => 'Leads, clients, renewals, tasks, and sales reports.', 'permissions' => ['dashboard.view', 'lead.', 'client.', 'renewal.view', 'task.', 'calendar.view', 'report.view']],
            ['name' => 'support_user', 'description' => 'Client issues, tasks, documents, and support communication.', 'permissions' => ['dashboard.view', 'issue.', 'client.view', 'task.', 'document.view', 'notification.view']],
            ['name' => 'client_user', 'description' => 'Client portal access for profile, documents, tasks, and issues.', 'permissions' => ['dashboard.view', 'profile.', 'document.view', 'issue.view', 'issue.create', 'task.view', 'notification.view']],
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