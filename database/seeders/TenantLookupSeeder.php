<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;

class TenantLookupSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        foreach ($this->lookups() as $group => $items) {
            foreach ($items as $index => $item) {
                $this->seedRecord('tenant_lookups', ['tenant_id' => null, 'group' => $group, 'code' => $item['code']], [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'color' => $item['color'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'sort_order' => $index + 1,
                    'is_default' => (bool) ($item['default'] ?? false),
                    'is_system' => true,
                    'status' => 'active',
                    'metadata' => isset($item['metadata']) ? json_encode($item['metadata']) : null,
                ], true);
            }
        }
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function lookups(): array
    {
        return [
            'lead_stage' => [
                ['code' => 'new', 'name' => 'New', 'color' => '#2563EB', 'default' => true],
                ['code' => 'contacted', 'name' => 'Contacted', 'color' => '#0891B2'],
                ['code' => 'qualified', 'name' => 'Qualified', 'color' => '#16A34A'],
                ['code' => 'proposal', 'name' => 'Proposal', 'color' => '#CA8A04'],
                ['code' => 'won', 'name' => 'Won', 'color' => '#15803D'],
                ['code' => 'lost', 'name' => 'Lost', 'color' => '#DC2626'],
            ],
            'priority' => [
                ['code' => 'low', 'name' => 'Low', 'color' => '#64748B'],
                ['code' => 'medium', 'name' => 'Medium', 'color' => '#2563EB', 'default' => true],
                ['code' => 'high', 'name' => 'High', 'color' => '#EA580C'],
                ['code' => 'urgent', 'name' => 'Urgent', 'color' => '#DC2626'],
                ['code' => 'critical', 'name' => 'Critical', 'color' => '#991B1B'],
            ],
            'party_status' => [
                ['code' => 'active', 'name' => 'Active', 'color' => '#16A34A', 'default' => true],
                ['code' => 'inactive', 'name' => 'Inactive', 'color' => '#64748B'],
                ['code' => 'blacklisted', 'name' => 'Blacklisted', 'color' => '#991B1B'],
            ],
            'project_status' => [
                ['code' => 'planned', 'name' => 'Planned', 'default' => true],
                ['code' => 'active', 'name' => 'Active'],
                ['code' => 'on_hold', 'name' => 'On Hold'],
                ['code' => 'completed', 'name' => 'Completed'],
                ['code' => 'cancelled', 'name' => 'Cancelled'],
            ],
            'task_status' => [
                ['code' => 'todo', 'name' => 'To Do', 'default' => true],
                ['code' => 'in_progress', 'name' => 'In Progress'],
                ['code' => 'review', 'name' => 'Review'],
                ['code' => 'done', 'name' => 'Done'],
                ['code' => 'blocked', 'name' => 'Blocked'],
            ],
            'issue_status' => [
                ['code' => 'open', 'name' => 'Open', 'default' => true],
                ['code' => 'assigned', 'name' => 'Assigned'],
                ['code' => 'in_progress', 'name' => 'In Progress'],
                ['code' => 'resolved', 'name' => 'Resolved'],
                ['code' => 'closed', 'name' => 'Closed'],
            ],
            'renewal_status' => [
                ['code' => 'active', 'name' => 'Active', 'default' => true],
                ['code' => 'due_soon', 'name' => 'Due Soon'],
                ['code' => 'renewed', 'name' => 'Renewed'],
                ['code' => 'expired', 'name' => 'Expired'],
                ['code' => 'cancelled', 'name' => 'Cancelled'],
            ],
            'category' => [
                ['code' => 'general', 'name' => 'General', 'default' => true],
                ['code' => 'sales', 'name' => 'Sales'],
                ['code' => 'support', 'name' => 'Support'],
                ['code' => 'finance', 'name' => 'Finance'],
                ['code' => 'operations', 'name' => 'Operations'],
            ],
            'employment_type' => [
                ['code' => 'full_time', 'name' => 'Full Time', 'default' => true],
                ['code' => 'part_time', 'name' => 'Part Time'],
                ['code' => 'contract', 'name' => 'Contract'],
                ['code' => 'intern', 'name' => 'Intern'],
                ['code' => 'consultant', 'name' => 'Consultant'],
            ],
            'team_type' => [
                ['code' => 'sales', 'name' => 'Sales'],
                ['code' => 'support', 'name' => 'Support'],
                ['code' => 'development', 'name' => 'Development'],
                ['code' => 'operations', 'name' => 'Operations'],
                ['code' => 'finance', 'name' => 'Finance'],
                ['code' => 'hr', 'name' => 'HR'],
                ['code' => 'management', 'name' => 'Management'],
                ['code' => 'branch', 'name' => 'Branch'],
                ['code' => 'project', 'name' => 'Project'],
            ],
        ];
    }
}