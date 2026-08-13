<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use App\Models\User;
use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoRelationalDataSeeder extends Seeder
{
    use SeedsRecords;

    private int $tenantId;
    private int $ownerId;
    private int $officeId;
    private int $platformUserId;

    public function run(): void
    {
        $this->tenantId = (int) DB::table('tenants')->where('slug', DemoTenantFoundationSeeder::TENANT_SLUG)->value('id');
        $this->ownerId = (int) DB::table('users')->where('tenant_id', $this->tenantId)->where('email', DemoTenantFoundationSeeder::OWNER_EMAIL)->value('id');
        $this->officeId = (int) DB::table('tenant_offices')->where('tenant_id', $this->tenantId)->where('office_code', 'HO')->value('id');
        $this->platformUserId = (int) DB::table('platform_users')->where('email', env('PLATFORM_SUPER_ADMIN_EMAIL', 'support@technofra.com'))->value('id');

        if (! $this->tenantId || ! $this->ownerId || ! $this->officeId) {
            return;
        }

        $this->seedRbacAssignments();
        $this->seedPlatformOperations();
        $this->seedSettingsAndFiles();
        $ids = $this->seedTenantPeopleAndTeams();
        $ids += $this->seedPartiesAndCrm($ids);
        $ids += $this->seedProjectsTasksAndCalendar($ids);
        $ids += $this->seedFinancePayrollAndHr($ids);
        $this->seedOperationsAndSupport($ids);
    }

    private function seedRbacAssignments(): void
    {
        $ownerRoleId = (int) DB::table('roles')->where('tenant_id', $this->tenantId)->where('name', 'owner')->value('id');
        if ($ownerRoleId) {
            $this->seedPivot('model_has_roles', [
                'tenant_id' => $this->tenantId,
                'role_id' => $ownerRoleId,
                'model_id' => $this->ownerId,
                'model_type' => User::class,
            ]);
        }

        $permissionId = (int) DB::table('permissions')->where('name', 'dashboard.view')->value('id');
        if ($permissionId) {
            $this->seedPivot('model_has_permissions', [
                'tenant_id' => $this->tenantId,
                'permission_id' => $permissionId,
                'model_id' => $this->ownerId,
                'model_type' => User::class,
            ]);
        }

        $platformPermissionId = (int) DB::table('platform_permissions')->where('name', 'dashboard.view')->value('id');
        if ($platformPermissionId && $this->platformUserId) {
            $this->seedPivot('platform_model_has_permissions', [
                'permission_id' => $platformPermissionId,
                'model_id' => $this->platformUserId,
                'model_type' => PlatformUser::class,
            ]);
        }
    }

    private function seedPlatformOperations(): void
    {
        $teamRoleId = $this->seedRecord('platform_team_roles', ['code' => 'demo-lead'], [
            'name' => 'Demo Lead',
            'description' => 'Owns demo tenant support.',
            'permissions' => json_encode(['tenant.view', 'support.ticket.view']),
            'sort_order' => 1,
            'is_system' => false,
            'status' => 'active',
        ], true);

        $teamId = $this->seedRecord('platform_teams', ['code' => 'demo-success'], [
            'name' => 'Demo Success',
            'description' => 'Platform success team for seeded accounts.',
            'lead_platform_user_id' => $this->platformUserId ?: null,
            'assistant_lead_platform_user_id' => null,
            'email' => 'success.demo@example.test',
            'phone' => '+919999000099',
            'color' => '#2563EB',
            'icon' => 'life-buoy',
            'visibility' => 'internal',
            'status' => 'active',
        ], true);

        if ($this->platformUserId) {
            $this->seedRecord('platform_team_members', ['platform_team_id' => $teamId, 'platform_user_id' => $this->platformUserId], [
                'platform_team_role_id' => $teamRoleId,
                'joined_at' => now()->subDays(12)->toDateString(),
                'status' => 'active',
            ]);
            $this->seedRecord('platform_team_assignments', ['platform_team_id' => $teamId, 'assignable_type' => 'tenants', 'assignable_id' => $this->tenantId], [
                'assignment_role' => 'success_owner',
                'assigned_by' => $this->platformUserId,
                'assigned_at' => now()->subDays(10),
                'status' => 'active',
            ]);
            $this->seedRecord('platform_api_tokens', ['token_hash' => hash('sha256', 'demo-platform-token')], [
                'name' => 'Demo Platform Token',
                'encrypted_token_preview' => 'plat_demo_********',
                'abilities' => json_encode(['dashboard.view']),
                'last_used_at' => now()->subHour(),
                'expires_at' => now()->addMonth(),
                'created_by' => $this->platformUserId,
            ], true);
            $this->seedRecord('platform_idempotency_keys', ['key' => 'demo-platform-seed', 'operation' => 'seed.demo', 'platform_user_id' => $this->platformUserId], [
                'request_hash' => hash('sha256', 'demo-platform-seed'),
                'status' => 'completed',
                'response_status' => 200,
                'response_body' => json_encode(['seeded' => true]),
            ]);
        }

        $moduleId = $this->seedRecord('modules', ['code' => 'demo-crm'], [
            'name' => 'Demo CRM',
            'description' => 'Demo CRM module switch.',
            'icon' => 'briefcase-business',
            'category' => 'crm',
            'is_core' => true,
            'status' => 'active',
            'sort_order' => 1,
        ], true);

        $this->seedRecord('tenant_module_overrides', ['tenant_id' => $this->tenantId, 'module_code' => 'demo-crm'], [
            'enabled' => true,
            'limits' => json_encode(['users' => 5]),
            'metadata' => json_encode(['module_id' => $moduleId]),
            'updated_by' => $this->platformUserId ?: null,
        ], true);
    }

    private function seedSettingsAndFiles(): void
    {
        $this->seedRecord('tenant_settings', ['tenant_id' => $this->tenantId, 'group' => 'general', 'key' => 'invoice_prefix'], [
            'value' => json_encode('DEMO-INV'),
            'value_type' => 'string',
            'updated_by' => $this->ownerId,
        ]);
        $this->seedRecord('user_preferences', ['tenant_id' => $this->tenantId, 'user_id' => $this->ownerId, 'group' => 'ui', 'key' => 'theme'], [
            'value' => json_encode('system'),
        ]);
        if ($this->platformUserId) {
            $this->seedRecord('platform_user_preferences', ['platform_user_id' => $this->platformUserId, 'group' => 'ui', 'key' => 'density'], [
                'value' => json_encode('compact'),
            ]);
            $this->seedRecord('platform_settings', ['group' => 'billing', 'key' => 'default_currency'], [
                'value' => json_encode('INR'),
                'value_type' => 'string',
                'updated_by' => $this->platformUserId,
            ]);
        }
    }

    /** @return array<string, int> */
    private function seedTenantPeopleAndTeams(): array
    {
        $fileId = $this->seedRecord('files', ['path' => 'demo/welcome.pdf'], [
            'tenant_id' => $this->tenantId,
            'disk' => 'local',
            'original_name' => 'welcome.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1024,
            'checksum' => hash('sha256', 'welcome.pdf'),
            'visibility' => 'tenant',
            'uploaded_by' => $this->ownerId,
            'platform_uploaded_by' => null,
        ], true);

        $folderId = $this->seedRecord('document_folders', ['tenant_id' => $this->tenantId, 'parent_id' => null, 'slug' => 'demo-documents'], [
            'name' => 'Demo Documents',
            'folder_type' => 'general',
            'created_by' => $this->ownerId,
        ], true);
        $this->seedRecord('document_folder_files', ['document_folder_id' => $folderId, 'file_id' => $fileId], [
            'tenant_id' => $this->tenantId,
            'created_by' => $this->ownerId,
        ]);

        $departmentId = $this->seedRecord('departments', ['tenant_id' => $this->tenantId, 'code' => 'OPS'], [
            'name' => 'Operations',
            'parent_id' => null,
            'manager_user_id' => $this->ownerId,
            'status' => 'active',
        ], true);
        $designationId = $this->seedRecord('designations', ['tenant_id' => $this->tenantId, 'code' => 'OPS-MGR'], [
            'department_id' => $departmentId,
            'name' => 'Operations Manager',
            'status' => 'active',
        ], true);
        $teamTypeId = $this->lookupId('team_type', 'operations');
        $teamId = $this->seedRecord('teams', ['tenant_id' => $this->tenantId, 'code' => 'OPS'], [
            'parent_team_id' => null,
            'department_id' => $departmentId,
            'office_id' => $this->officeId,
            'team_type_id' => $teamTypeId,
            'name' => 'Operations Team',
            'description' => 'Seeded operations team.',
            'lead_user_id' => $this->ownerId,
            'assistant_lead_user_id' => null,
            'email' => 'ops.demo@example.test',
            'phone' => '+919999000002',
            'color' => '#0F766E',
            'icon' => 'settings',
            'visibility' => 'tenant',
            'is_default' => true,
            'status' => 'active',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $teamRoleId = $this->seedRecord('team_roles', ['tenant_id' => $this->tenantId, 'code' => 'LEAD'], [
            'name' => 'Lead',
            'description' => 'Team lead role.',
            'permissions' => json_encode(['task.assign']),
            'sort_order' => 1,
            'is_system' => false,
            'status' => 'active',
        ], true);
        $staffId = $this->seedRecord('staff', ['tenant_id' => $this->tenantId, 'employee_code' => 'EMP-DEMO-001'], [
            'first_name' => 'Demo',
            'last_name' => 'Owner',
            'display_name' => 'Demo Owner',
            'work_email' => DemoTenantFoundationSeeder::OWNER_EMAIL,
            'mobile' => '+919999000001',
            'joining_date' => now()->subMonths(3)->toDateString(),
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'office_id' => $this->officeId,
            'primary_team_id' => $teamId,
            'reporting_manager_id' => null,
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);

        DB::table('users')->where('id', $this->ownerId)->update(['staff_id' => $staffId, 'updated_at' => now()]);
        DB::table('departments')->where('id', $departmentId)->update(['manager_user_id' => $this->ownerId, 'updated_at' => now()]);

        $this->seedRecord('team_members', ['tenant_id' => $this->tenantId, 'team_id' => $teamId, 'user_id' => $this->ownerId, 'effective_from' => now()->subMonths(3)->toDateString()], [
            'staff_id' => $staffId,
            'team_role_id' => $teamRoleId,
            'member_type' => 'lead',
            'allocation_percent' => 100,
            'is_primary' => true,
            'joined_at' => now()->subMonths(3),
            'status' => 'active',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $permissionId = (int) DB::table('permissions')->where('name', 'task.assign')->value('id');
        if ($permissionId) {
            $this->seedRecord('team_permissions', ['tenant_id' => $this->tenantId, 'team_id' => $teamId, 'permission_id' => $permissionId], [
                'granted_by' => $this->ownerId,
                'granted_at' => now(),
            ]);
        }
        $this->seedRecord('team_settings', ['tenant_id' => $this->tenantId, 'team_id' => $teamId, 'group' => 'workflow', 'key' => 'daily_digest'], [
            'value' => json_encode(true),
            'value_type' => 'boolean',
        ]);

        return compact('fileId', 'folderId', 'departmentId', 'designationId', 'teamId', 'teamRoleId', 'staffId');
    }

    /** @param array<string, int> $ids @return array<string, int> */
    private function seedPartiesAndCrm(array $ids): array
    {
        $industryId = (int) DB::table('industries')->where('code', 'it_services')->value('id');
        $partyStatusId = $this->lookupId('party_status', 'active');
        $sourceId = $this->lookupId('category', 'sales');

        $clientPartyId = $this->seedRecord('parties', ['tenant_id' => $this->tenantId, 'display_name' => 'Acme Client'], [
            'party_type' => 'client',
            'legal_name' => 'Acme Client Private Limited',
            'email' => 'billing.acme@example.test',
            'phone' => '+919999000010',
            'website' => 'https://acme.example.test',
            'industry_id' => $industryId ?: null,
            'source_id' => $sourceId,
            'status_id' => $partyStatusId,
            'owner_user_id' => $this->ownerId,
            'metadata' => json_encode(['segment' => 'demo']),
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $vendorPartyId = $this->seedRecord('parties', ['tenant_id' => $this->tenantId, 'display_name' => 'Orbit Vendor'], [
            'party_type' => 'vendor',
            'legal_name' => 'Orbit Vendor LLP',
            'email' => 'accounts.orbit@example.test',
            'phone' => '+919999000011',
            'industry_id' => $industryId ?: null,
            'status_id' => $partyStatusId,
            'owner_user_id' => $this->ownerId,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $leadPartyId = $this->seedRecord('parties', ['tenant_id' => $this->tenantId, 'display_name' => 'Nova Lead'], [
            'party_type' => 'lead',
            'email' => 'hello.nova@example.test',
            'phone' => '+919999000012',
            'industry_id' => $industryId ?: null,
            'source_id' => $sourceId,
            'status_id' => $partyStatusId,
            'owner_user_id' => $this->ownerId,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);

        $contactId = $this->seedRecord('party_contacts', ['tenant_id' => $this->tenantId, 'party_id' => $clientPartyId, 'email' => 'priya.acme@example.test'], [
            'first_name' => 'Priya',
            'last_name' => 'Shah',
            'display_name' => 'Priya Shah',
            'mobile' => '+919999000013',
            'designation' => 'Operations Head',
            'department' => 'Operations',
            'is_primary' => true,
            'portal_enabled' => true,
            'status' => 'active',
        ], true);
        $this->seedRecord('party_addresses', ['tenant_id' => $this->tenantId, 'party_id' => $clientPartyId, 'address_type' => 'billing'], [
            'address_line_1' => 'Acme Tower',
            'country_id' => (int) DB::table('countries')->where('iso2', 'IN')->value('id') ?: null,
            'state_id' => (int) DB::table('states')->where('code', 'GJ')->value('id') ?: null,
            'city_id' => (int) DB::table('cities')->where('name', 'Ahmedabad')->value('id') ?: null,
            'postal_code' => '380015',
            'is_default' => true,
        ]);
        $clientProfileId = $this->seedRecord('client_profiles', ['tenant_id' => $this->tenantId, 'party_id' => $clientPartyId], [
            'client_code' => 'CLI-DEMO-001',
            'client_type' => 'business',
            'credit_limit' => 100000,
            'payment_terms_days' => 15,
            'onboarding_date' => now()->subMonth()->toDateString(),
            'account_manager_id' => $this->ownerId,
        ]);
        $vendorProfileId = $this->seedRecord('vendor_profiles', ['tenant_id' => $this->tenantId, 'party_id' => $vendorPartyId], [
            'vendor_code' => 'VEN-DEMO-001',
            'vendor_category_id' => $this->lookupId('category', 'operations'),
            'payment_terms_days' => 30,
            'rating' => 4.50,
            'account_manager_id' => $this->ownerId,
        ]);
        $leadProfileId = $this->seedRecord('lead_profiles', ['tenant_id' => $this->tenantId, 'party_id' => $leadPartyId], [
            'lead_number' => 'LEAD-DEMO-001',
            'stage_id' => $this->lookupId('lead_stage', 'qualified'),
            'priority_id' => $this->lookupId('priority', 'high'),
            'expected_value' => 250000,
            'probability' => 65,
            'expected_close_date' => now()->addDays(21)->toDateString(),
        ]);
        $this->seedRecord('lead_activities', ['tenant_id' => $this->tenantId, 'lead_profile_id' => $leadProfileId, 'subject' => 'Discovery call'], [
            'activity_type' => 'call',
            'description' => 'Discussed CRM rollout requirements.',
            'scheduled_at' => now()->addDays(2),
            'assigned_to' => $this->ownerId,
            'created_by' => $this->ownerId,
        ], true);
        $this->seedRecord('lead_conversion_history', ['tenant_id' => $this->tenantId, 'lead_profile_id' => $leadProfileId, 'client_party_id' => $clientPartyId], [
            'converted_by' => $this->ownerId,
            'conversion_note' => 'Demo conversion trail.',
            'metadata' => json_encode(['source_party_id' => $leadPartyId]),
            'converted_at' => now()->subDays(3),
        ]);

        $tagId = $this->seedRecord('tags', ['tenant_id' => $this->tenantId, 'slug' => 'priority-client'], [
            'name' => 'Priority Client',
            'color' => '#DC2626',
            'icon' => 'star',
            'status' => 'active',
        ], true);
        $this->seedPivot('taggables', [
            'tenant_id' => $this->tenantId,
            'tag_id' => $tagId,
            'taggable_type' => 'parties',
            'taggable_id' => $clientPartyId,
        ], ['created_at' => now()]);
        $customFieldId = $this->seedRecord('custom_fields', ['tenant_id' => $this->tenantId, 'entity_type' => 'parties', 'code' => 'health_score'], [
            'name' => 'Health Score',
            'field_type' => 'number',
            'options' => null,
            'validation_rules' => json_encode(['min' => 0, 'max' => 100]),
            'is_required' => false,
            'sort_order' => 1,
            'status' => 'active',
        ], true);
        $this->seedRecord('custom_field_values', ['tenant_id' => $this->tenantId, 'custom_field_id' => $customFieldId, 'entity_type' => 'parties', 'entity_id' => $clientPartyId], [
            'value_number' => 88,
        ]);
        $this->seedRecord('attachments', ['tenant_id' => $this->tenantId, 'file_id' => $ids['fileId'], 'attachable_type' => 'parties', 'attachable_id' => $clientPartyId], [
            'label' => 'Welcome pack',
            'created_by' => $this->ownerId,
            'created_at' => now(),
        ]);
        $this->seedRecord('notes', ['tenant_id' => $this->tenantId, 'notable_type' => 'parties', 'notable_id' => $clientPartyId], [
            'note' => 'Seeded client is ready for demos.',
            'visibility' => 'tenant',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);

        return compact('clientPartyId', 'vendorPartyId', 'leadPartyId', 'contactId', 'clientProfileId', 'vendorProfileId', 'leadProfileId', 'tagId', 'customFieldId');
    }

    /** @param array<string, int> $ids @return array<string, int> */
    private function seedProjectsTasksAndCalendar(array $ids): array
    {
        $projectId = $this->seedRecord('projects', ['tenant_id' => $this->tenantId, 'project_number' => 'PROJ-DEMO-001'], [
            'name' => 'CRM Implementation',
            'description' => 'Seeded implementation project.',
            'client_party_id' => $ids['clientPartyId'],
            'project_manager_id' => $this->ownerId,
            'category_id' => $this->lookupId('category', 'operations'),
            'status_id' => $this->lookupId('project_status', 'active'),
            'priority_id' => $this->lookupId('priority', 'high'),
            'start_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'budget_amount' => 180000,
            'billing_type' => 'fixed',
            'progress' => 35,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('project_members', ['tenant_id' => $this->tenantId, 'project_id' => $projectId, 'user_id' => $this->ownerId], [
            'team_id' => $ids['teamId'],
            'role_id' => $this->lookupId('category', 'operations'),
            'billing_rate' => 1500,
            'allocation_percent' => 80,
            'joined_at' => now()->subDays(7),
        ]);
        $phaseId = $this->seedRecord('project_phases', ['tenant_id' => $this->tenantId, 'project_id' => $projectId, 'name' => 'Discovery'], [
            'start_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'status_id' => $this->lookupId('project_status', 'active'),
            'sort_order' => 1,
        ]);
        $milestoneId = $this->seedRecord('project_milestones', ['tenant_id' => $this->tenantId, 'project_id' => $projectId, 'name' => 'Requirements signed'], [
            'phase_id' => $phaseId,
            'due_date' => now()->addDays(5)->toDateString(),
            'status_id' => $this->lookupId('project_status', 'planned'),
        ]);
        $taskId = $this->seedRecord('tasks', ['tenant_id' => $this->tenantId, 'task_number' => 'TASK-DEMO-001'], [
            'project_id' => $projectId,
            'related_type' => 'project_milestones',
            'related_id' => $milestoneId,
            'title' => 'Prepare requirements checklist',
            'description' => 'Collect initial CRM setup requirements.',
            'status_id' => $this->lookupId('task_status', 'in_progress'),
            'priority_id' => $this->lookupId('priority', 'high'),
            'category_id' => $this->lookupId('category', 'operations'),
            'assigned_to' => $this->ownerId,
            'assigned_team_id' => $ids['teamId'],
            'assigned_by' => $this->ownerId,
            'start_at' => now()->subDay(),
            'due_at' => now()->addDays(4),
            'estimated_minutes' => 240,
            'actual_minutes' => 60,
            'progress' => 25,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $checklistId = $this->seedRecord('task_checklists', ['tenant_id' => $this->tenantId, 'task_id' => $taskId, 'title' => 'Discovery items'], ['sort_order' => 1]);
        $this->seedRecord('task_checklist_items', ['tenant_id' => $this->tenantId, 'checklist_id' => $checklistId, 'title' => 'Confirm user roles'], [
            'is_completed' => false,
            'sort_order' => 1,
        ]);
        $this->seedRecord('task_comments', ['tenant_id' => $this->tenantId, 'task_id' => $taskId, 'comment' => 'Initial checklist added.'], [
            'user_id' => $this->ownerId,
            'created_at' => now(),
        ]);
        $this->seedPivot('task_watchers', ['tenant_id' => $this->tenantId, 'task_id' => $taskId, 'user_id' => $this->ownerId]);
        $this->seedRecord('task_assignments', ['tenant_id' => $this->tenantId, 'task_id' => $taskId, 'assigned_to' => $this->ownerId], [
            'assigned_team_id' => $ids['teamId'],
            'assigned_by' => $this->ownerId,
            'assigned_at' => now(),
            'remarks' => 'Demo assignment',
        ]);
        $this->seedRecord('task_time_logs', ['tenant_id' => $this->tenantId, 'task_id' => $taskId, 'user_id' => $this->ownerId, 'started_at' => now()->subHours(2)], [
            'ended_at' => now()->subHour(),
            'minutes' => 60,
            'notes' => 'Requirements review.',
        ]);
        $dependentTaskId = $this->seedRecord('tasks', ['tenant_id' => $this->tenantId, 'task_number' => 'TASK-DEMO-002'], [
            'parent_task_id' => $taskId,
            'project_id' => $projectId,
            'related_type' => 'projects',
            'related_id' => $projectId,
            'title' => 'Configure user roles',
            'description' => 'Create initial owner and operations role setup.',
            'status_id' => $this->lookupId('task_status', 'todo'),
            'priority_id' => $this->lookupId('priority', 'medium'),
            'category_id' => $this->lookupId('category', 'operations'),
            'assigned_to' => $this->ownerId,
            'assigned_team_id' => $ids['teamId'],
            'assigned_by' => $this->ownerId,
            'start_at' => now()->addDays(1),
            'due_at' => now()->addDays(6),
            'estimated_minutes' => 180,
            'progress' => 0,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('task_dependencies', ['tenant_id' => $this->tenantId, 'task_id' => $dependentTaskId, 'depends_on_task_id' => $taskId], [
            'dependency_type' => 'finish_to_start',
        ]);
        $this->seedRecord('project_time_logs', ['tenant_id' => $this->tenantId, 'project_id' => $projectId, 'user_id' => $this->ownerId, 'started_at' => now()->subHours(3)], [
            'task_id' => $taskId,
            'ended_at' => now()->subHours(2),
            'minutes' => 60,
            'billable' => true,
        ]);
        $this->seedRecord('project_expenses', ['tenant_id' => $this->tenantId, 'project_id' => $projectId, 'expense_date' => now()->toDateString()], [
            'vendor_party_id' => $ids['vendorPartyId'],
            'amount' => 2500,
            'currency' => 'INR',
            'status_id' => $this->lookupId('issue_status', 'open'),
        ]);
        $this->seedRecord('team_assignments', ['tenant_id' => $this->tenantId, 'team_id' => $ids['teamId'], 'assignable_type' => 'projects', 'assignable_id' => $projectId], [
            'assignment_role' => 'delivery',
            'assigned_by' => $this->ownerId,
            'assigned_at' => now(),
            'status' => 'active',
        ]);
        $this->seedRecord('todo_lists', ['tenant_id' => $this->tenantId, 'name' => 'Demo Todo'], [
            'description' => 'Seeded work list.',
            'owner_user_id' => $this->ownerId,
            'team_id' => $ids['teamId'],
            'visibility' => 'team',
            'color' => '#2563EB',
            'icon' => 'list-checks',
            'is_default' => true,
            'status' => 'active',
        ], true);
        $issueId = $this->seedRecord('client_issues', ['tenant_id' => $this->tenantId, 'issue_number' => 'ISS-DEMO-001'], [
            'client_party_id' => $ids['clientPartyId'],
            'contact_id' => $ids['contactId'],
            'project_id' => $projectId,
            'title' => 'Portal access clarification',
            'description' => 'Client needs role details.',
            'status_id' => $this->lookupId('issue_status', 'open'),
            'priority_id' => $this->lookupId('priority', 'medium'),
            'assigned_to' => $this->ownerId,
            'assigned_team_id' => $ids['teamId'],
            'due_at' => now()->addDays(3),
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $renewalId = $this->seedRecord('renewals', ['tenant_id' => $this->tenantId, 'renewal_number' => 'REN-DEMO-001'], [
            'party_id' => $ids['clientPartyId'],
            'renewal_type' => 'contract',
            'title' => 'CRM Support Contract',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'renewal_date' => now()->addYear()->subDays(30)->toDateString(),
            'amount' => 60000,
            'currency' => 'INR',
            'reminder_days_before' => 30,
            'auto_renew' => false,
            'status_id' => $this->lookupId('renewal_status', 'active'),
            'owner_user_id' => $this->ownerId,
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('renewal_items', ['tenant_id' => $this->tenantId, 'renewal_id' => $renewalId, 'name' => 'Annual Support'], [
            'quantity' => 1,
            'unit_price' => 60000,
            'amount' => 60000,
        ]);
        $this->seedRecord('renewal_history', ['tenant_id' => $this->tenantId, 'renewal_id' => $renewalId, 'remarks' => 'Contract created'], [
            'old_end_date' => null,
            'new_end_date' => now()->addYear()->toDateString(),
            'status_id' => $this->lookupId('renewal_status', 'active'),
            'created_by' => $this->ownerId,
            'created_at' => now(),
        ]);
        $this->seedRecord('renewal_reminders', ['tenant_id' => $this->tenantId, 'renewal_id' => $renewalId, 'channel' => 'email'], [
            'remind_at' => now()->addYear()->subDays(30),
            'status' => 'pending',
        ]);
        $calendarId = $this->seedRecord('calendars', ['tenant_id' => $this->tenantId, 'name' => 'Demo Calendar'], [
            'owner_user_id' => $this->ownerId,
            'team_id' => $ids['teamId'],
            'calendar_type' => 'team',
            'color' => '#0F766E',
            'timezone' => 'Asia/Kolkata',
            'visibility' => 'team',
            'status' => 'active',
        ], true);
        $eventId = $this->seedRecord('calendar_events', ['tenant_id' => $this->tenantId, 'calendar_id' => $calendarId, 'title' => 'Discovery Review'], [
            'related_type' => 'projects',
            'related_id' => $projectId,
            'description' => 'Review CRM requirements.',
            'location' => 'Conference Room',
            'starts_at' => now()->addDays(2)->setTime(11, 0),
            'ends_at' => now()->addDays(2)->setTime(12, 0),
            'timezone' => 'Asia/Kolkata',
            'status' => 'scheduled',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('calendar_event_attendees', ['tenant_id' => $this->tenantId, 'event_id' => $eventId, 'attendee_type' => 'user', 'user_id' => $this->ownerId], [
            'response_status' => 'accepted',
        ]);
        $this->seedRecord('calendar_event_reminders', ['tenant_id' => $this->tenantId, 'event_id' => $eventId, 'channel' => 'email'], [
            'remind_at' => now()->addDays(2)->setTime(10, 30),
            'status' => 'pending',
        ]);
        $roomId = $this->seedRecord('meeting_rooms', ['tenant_id' => $this->tenantId, 'name' => 'Demo Room'], [
            'office_id' => $this->officeId,
            'location' => 'Head Office',
            'capacity' => 6,
            'status' => 'active',
        ]);
        $this->seedRecord('meeting_room_bookings', ['tenant_id' => $this->tenantId, 'room_id' => $roomId, 'event_id' => $eventId], [
            'booked_by' => $this->ownerId,
            'status' => 'booked',
        ]);
        $this->seedRecord('video_meetings', ['tenant_id' => $this->tenantId, 'event_id' => $eventId, 'provider' => 'google_meet'], [
            'meeting_id' => 'demo-meet-001',
            'meeting_url' => 'https://meet.example.test/demo-meet-001',
            'passcode' => '123456',
        ]);
        $this->seedRecord('calendar_sync_logs', ['tenant_id' => $this->tenantId, 'calendar_id' => $calendarId, 'provider' => 'google'], [
            'external_event_id' => 'demo-event-001',
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);
        $this->seedRecord('reminders', ['tenant_id' => $this->tenantId, 'remindable_type' => 'tasks', 'remindable_id' => $taskId, 'user_id' => $this->ownerId], [
            'channel' => 'email',
            'remind_at' => now()->addDay(),
            'status' => 'pending',
            'metadata' => json_encode(['task_number' => 'TASK-DEMO-001']),
        ], true);

        return compact('projectId', 'phaseId', 'milestoneId', 'taskId', 'dependentTaskId', 'checklistId', 'issueId', 'renewalId', 'calendarId', 'eventId', 'roomId');
    }

    /** @param array<string, int> $ids @return array<string, int> */
    private function seedFinancePayrollAndHr(array $ids): array
    {
        $invoiceId = $this->seedRecord('tenant_invoices', ['tenant_id' => $this->tenantId, 'invoice_number' => 'TINV-DEMO-001'], [
            'client_party_id' => $ids['clientPartyId'],
            'project_id' => $ids['projectId'],
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => 50000,
            'taxable_amount' => 50000,
            'tax_amount' => 9000,
            'total_amount' => 59000,
            'paid_amount' => 0,
            'balance_amount' => 59000,
            'currency' => 'INR',
            'status' => 'sent',
            'pdf_file_id' => $ids['fileId'],
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('tenant_invoice_items', ['tenant_id' => $this->tenantId, 'invoice_id' => $invoiceId, 'item_name' => 'CRM Setup'], [
            'description' => 'Implementation milestone billing.',
            'quantity' => 1,
            'unit_price' => 50000,
            'tax_rate' => 18,
            'amount' => 50000,
        ]);
        $paymentId = $this->seedRecord('tenant_payments', ['tenant_id' => $this->tenantId, 'payment_number' => 'TPAY-DEMO-001'], [
            'invoice_id' => $invoiceId,
            'client_party_id' => $ids['clientPartyId'],
            'amount' => 10000,
            'currency' => 'INR',
            'method' => 'bank_transfer',
            'reference' => 'UTR-DEMO-001',
            'status' => 'received',
            'paid_at' => now(),
        ], true);
        $expenseId = $this->seedRecord('tenant_expenses', ['tenant_id' => $this->tenantId, 'expense_number' => 'TEXP-DEMO-001'], [
            'vendor_party_id' => $ids['vendorPartyId'],
            'project_id' => $ids['projectId'],
            'category_id' => $this->lookupId('category', 'operations'),
            'amount' => 2500,
            'currency' => 'INR',
            'expense_date' => now()->toDateString(),
            'status_id' => $this->lookupId('issue_status', 'open'),
        ], true);
        $this->seedRecord('tenant_expense_items', ['tenant_id' => $this->tenantId, 'expense_id' => $expenseId, 'description' => 'Demo travel'], [
            'quantity' => 1,
            'unit_price' => 2500,
            'tax_amount' => 0,
            'amount' => 2500,
        ]);
        $bankAccountId = $this->seedRecord('bank_accounts', ['tenant_id' => $this->tenantId, 'owner_type' => 'tenants', 'owner_id' => $this->tenantId], [
            'bank_name' => 'Demo Bank',
            'account_number_encrypted' => 'encrypted-demo-account',
            'routing_number_encrypted' => 'encrypted-demo-routing',
            'ifsc_code' => 'DEMO0001234',
            'is_primary' => true,
        ]);

        $this->seedRecord('staff_employment_history', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'effective_from' => now()->subMonths(3)->toDateString()], [
            'department_id' => $ids['departmentId'],
            'designation_id' => $ids['designationId'],
            'office_id' => $this->officeId,
        ]);
        $this->seedRecord('staff_bank_accounts', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'bank_name' => 'Demo Bank'], [
            'account_holder_name' => 'Demo Owner',
            'account_number_encrypted' => 'encrypted-staff-account',
            'ifsc_code' => 'DEMO0001234',
            'is_primary' => true,
        ]);
        $this->seedRecord('staff_salary_structures', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'effective_from' => now()->subMonths(3)->toDateString()], [
            'annual_ctc' => 900000,
            'monthly_gross' => 75000,
            'currency' => 'INR',
        ]);
        $this->seedRecord('staff_documents', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'file_id' => $ids['fileId']], [
            'document_type_id' => $this->lookupId('category', 'general'),
            'document_number' => 'DOC-DEMO-001',
        ]);
        $this->seedRecord('staff_emergency_contacts', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'mobile' => '+919999000014'], [
            'name' => 'Emergency Contact',
            'relation' => 'Family',
            'email' => 'emergency.demo@example.test',
            'address' => 'Ahmedabad',
        ]);
        $this->seedRecord('staff_assets', ['tenant_id' => $this->tenantId, 'asset_code' => 'ASSET-DEMO-001'], [
            'staff_id' => $ids['staffId'],
            'asset_name' => 'Demo Laptop',
            'issued_at' => now()->subMonth()->toDateString(),
            'status' => 'issued',
        ]);
        $this->seedRecord('staff_certifications', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'name' => 'CRM Onboarding'], [
            'provider' => 'Technofra',
            'issued_on' => now()->subMonth()->toDateString(),
            'file_id' => $ids['fileId'],
        ]);
        $this->seedRecord('staff_appraisals', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'review_period' => '2026-H1'], [
            'rating' => 4.25,
            'reviewed_by' => $this->ownerId,
            'reviewed_at' => now()->subDays(10),
        ]);
        $this->seedRecord('staff_training', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'training_name' => 'CRM Workflow'], [
            'provider' => 'Technofra',
            'started_on' => now()->subDays(5)->toDateString(),
            'completed_on' => now()->subDays(2)->toDateString(),
            'status' => 'completed',
        ]);

        $shiftId = $this->seedRecord('shifts', ['tenant_id' => $this->tenantId, 'name' => 'General Shift'], [
            'start_time' => '09:30:00',
            'end_time' => '18:30:00',
            'break_minutes' => 60,
            'status' => 'active',
        ]);
        $this->seedRecord('staff_shift_assignments', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'shift_id' => $shiftId, 'effective_from' => now()->subMonth()->toDateString()], []);
        $attendanceId = $this->seedRecord('attendance_records', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'attendance_date' => now()->toDateString()], [
            'check_in_at' => now()->setTime(9, 30),
            'check_out_at' => now()->setTime(18, 30),
            'total_minutes' => 480,
            'status_id' => $this->lookupId('issue_status', 'open'),
        ]);
        $this->seedRecord('attendance_requests', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'request_date' => now()->toDateString(), 'request_type' => 'regularization'], [
            'reason' => 'Demo regularization request.',
            'status' => 'approved',
            'approved_by' => $this->ownerId,
            'approved_at' => now(),
        ], true);
        $leaveTypeId = $this->seedRecord('leave_types', ['tenant_id' => $this->tenantId, 'code' => 'CL'], [
            'name' => 'Casual Leave',
            'paid' => true,
            'carry_forward' => false,
            'status' => 'active',
        ]);
        $this->seedRecord('leave_requests', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'leave_type_id' => $leaveTypeId, 'start_date' => now()->addDays(12)->toDateString()], [
            'end_date' => now()->addDays(12)->toDateString(),
            'total_days' => 1,
            'reason' => 'Personal work',
            'status_id' => $this->lookupId('issue_status', 'open'),
            'approved_by' => $this->ownerId,
            'approved_at' => now(),
        ]);
        $this->seedRecord('leave_balances', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'leave_type_id' => $leaveTypeId, 'year' => (int) now()->format('Y')], [
            'opening_balance' => 12,
            'accrued' => 6,
            'used' => 1,
            'remaining' => 17,
        ]);

        $payrollCycleId = $this->seedRecord('payroll_cycles', ['tenant_id' => $this->tenantId, 'payroll_month' => (int) now()->format('m'), 'payroll_year' => (int) now()->format('Y')], [
            'cycle_name' => now()->format('F Y'),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'payment_date' => now()->endOfMonth()->toDateString(),
            'status' => 'approved',
            'processed_by' => $this->ownerId,
            'approved_by' => $this->ownerId,
            'processed_at' => now(),
            'approved_at' => now(),
            'remarks' => 'Seeded payroll cycle.',
        ], true);
        $payrollId = $this->seedRecord('payrolls', ['tenant_id' => $this->tenantId, 'payroll_cycle_id' => $payrollCycleId, 'staff_id' => $ids['staffId']], [
            'employee_code' => 'EMP-DEMO-001',
            'working_days' => 22,
            'present_days' => 21,
            'leave_days' => 1,
            'gross_salary' => 75000,
            'total_earnings' => 75000,
            'total_deductions' => 7500,
            'taxable_income' => 67500,
            'tax_amount' => 5000,
            'net_salary' => 67500,
            'payment_status' => 'paid',
            'payment_reference' => 'SAL-DEMO-001',
        ], true);
        $componentTypeId = $this->seedRecord('payroll_component_types', ['tenant_id' => $this->tenantId, 'code' => 'EARN'], [
            'name' => 'Earnings',
            'calculation_side' => 'earning',
            'status' => 'active',
        ]);
        $componentId = $this->seedRecord('payroll_components', ['tenant_id' => $this->tenantId, 'code' => 'BASIC'], [
            'component_type_id' => $componentTypeId,
            'name' => 'Basic Salary',
            'calculation_method' => 'fixed',
            'default_value' => 45000,
            'taxable' => true,
            'affects_pf' => true,
            'affects_esi' => false,
            'status' => 'active',
        ]);
        $this->seedRecord('payroll_component_assignments', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'component_id' => $componentId, 'effective_from' => now()->subMonths(3)->toDateString()], [
            'amount' => 45000,
        ]);
        $this->seedRecord('payroll_items', ['tenant_id' => $this->tenantId, 'payroll_id' => $payrollId, 'component_id' => $componentId], [
            'amount' => 45000,
            'calculation_type' => 'fixed',
            'remarks' => 'Basic component.',
        ]);
        $this->seedRecord('payroll_overtime', ['tenant_id' => $this->tenantId, 'payroll_id' => $payrollId], [
            'attendance_record_id' => $attendanceId,
            'overtime_hours' => 2,
            'hourly_rate' => 500,
            'amount' => 1000,
            'approved_by' => $this->ownerId,
        ]);
        $loanId = $this->seedRecord('payroll_loans', ['tenant_id' => $this->tenantId, 'loan_number' => 'LOAN-DEMO-001'], [
            'staff_id' => $ids['staffId'],
            'principal_amount' => 10000,
            'interest_rate' => 0,
            'installment_amount' => 1000,
            'remaining_amount' => 9000,
            'total_installments' => 10,
            'issued_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);
        $this->seedRecord('payroll_loan_installments', ['tenant_id' => $this->tenantId, 'loan_id' => $loanId, 'installment_no' => 1], [
            'payroll_id' => $payrollId,
            'amount' => 1000,
            'paid_at' => now(),
        ]);
        $this->seedRecord('payroll_reimbursements', ['tenant_id' => $this->tenantId, 'staff_id' => $ids['staffId'], 'expense_id' => $expenseId], [
            'payroll_id' => $payrollId,
            'amount' => 2500,
            'approval_status' => 'approved',
        ]);
        $taxSlabId = $this->seedRecord('payroll_tax_slabs', ['tenant_id' => $this->tenantId, 'name' => 'Demo Slab', 'effective_from' => now()->startOfYear()->toDateString()], [
            'min_amount' => 500000,
            'max_amount' => 1000000,
            'tax_percentage' => 10,
            'cess_percentage' => 4,
        ]);
        $this->seedRecord('payroll_tax_deductions', ['tenant_id' => $this->tenantId, 'payroll_id' => $payrollId], [
            'tax_slab_id' => $taxSlabId,
            'taxable_income' => 67500,
            'tax_amount' => 5000,
        ]);
        $this->seedRecord('payroll_pf_settings', ['tenant_id' => $this->tenantId, 'effective_from' => now()->startOfYear()->toDateString()], [
            'employee_rate' => 12,
            'employer_rate' => 12,
            'wage_limit' => 15000,
        ]);
        $this->seedRecord('payroll_esi_settings', ['tenant_id' => $this->tenantId, 'effective_from' => now()->startOfYear()->toDateString()], [
            'employee_rate' => 0.75,
            'employer_rate' => 3.25,
            'wage_limit' => 21000,
        ]);
        $this->seedRecord('payroll_bank_transfers', ['tenant_id' => $this->tenantId, 'payroll_id' => $payrollId], [
            'bank_account_id' => $bankAccountId,
            'reference' => 'NEFT-DEMO-001',
            'amount' => 67500,
            'transfer_date' => now()->toDateString(),
            'status' => 'processed',
        ]);
        $this->seedRecord('payroll_payslips', ['tenant_id' => $this->tenantId, 'payslip_number' => 'PAYSLIP-DEMO-001'], [
            'payroll_id' => $payrollId,
            'file_id' => $ids['fileId'],
            'generated_at' => now(),
            'emailed_at' => now(),
        ]);
        $this->seedRecord('payroll_approvals', ['tenant_id' => $this->tenantId, 'payroll_id' => $payrollId, 'approval_level' => 1], [
            'approved_by' => $this->ownerId,
            'status' => 'approved',
            'remarks' => 'Seeded approval.',
            'approved_at' => now(),
        ]);

        $holidayCalendarId = $this->seedRecord('holiday_calendars', ['tenant_id' => $this->tenantId, 'name' => 'Demo Holidays'], [
            'description' => 'Default holiday calendar.',
            'country_id' => (int) DB::table('countries')->where('iso2', 'IN')->value('id') ?: null,
            'state_id' => (int) DB::table('states')->where('code', 'GJ')->value('id') ?: null,
            'is_default' => true,
            'status' => 'active',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $holidayId = $this->seedRecord('holidays', ['tenant_id' => $this->tenantId, 'holiday_calendar_id' => $holidayCalendarId, 'holiday_date' => now()->addMonths(2)->toDateString()], [
            'name' => 'Demo Holiday',
            'type_id' => $this->lookupId('category', 'general'),
            'category_id' => $this->lookupId('category', 'general'),
            'start_date' => now()->addMonths(2)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'total_days' => 1,
            'applicable_to_all' => true,
            'description' => 'Seeded holiday.',
            'color' => '#16A34A',
            'created_by' => $this->ownerId,
            'updated_by' => $this->ownerId,
        ], true);
        $this->seedRecord('holiday_applicabilities', ['tenant_id' => $this->tenantId, 'holiday_id' => $holidayId, 'applicable_type' => 'office', 'applicable_id' => $this->officeId], [
            'created_at' => now(),
        ]);
        $holidayGroupId = $this->seedRecord('holiday_groups', ['tenant_id' => $this->tenantId, 'name' => 'Demo Group'], [
            'description' => 'Seeded holiday group.',
            'status' => 'active',
        ], true);
        $this->seedRecord('holiday_group_members', ['tenant_id' => $this->tenantId, 'holiday_group_id' => $holidayGroupId, 'staff_id' => $ids['staffId']], [
            'assigned_at' => now(),
        ]);

        return compact('invoiceId', 'paymentId', 'expenseId', 'bankAccountId', 'shiftId', 'attendanceId', 'leaveTypeId', 'payrollCycleId', 'payrollId', 'componentTypeId', 'componentId', 'loanId', 'taxSlabId', 'holidayCalendarId', 'holidayId', 'holidayGroupId');
    }

    /** @param array<string, int> $ids */
    private function seedOperationsAndSupport(array $ids): void
    {
        $planId = (int) DB::table('plans')->where('code', 'growth')->value('id') ?: (int) DB::table('plans')->value('id');
        $featureId = (int) DB::table('features')->where('code', 'users.limit')->value('id') ?: (int) DB::table('features')->value('id');
        $addonPlanId = (int) DB::table('addon_plans')->where('code', 'extra_10_users')->value('id') ?: (int) DB::table('addon_plans')->value('id');
        $subscriptionId = $this->seedRecord('subscriptions', ['subscription_number' => 'SUB-DEMO-001'], [
            'tenant_id' => $this->tenantId,
            'plan_id' => $planId,
            'current_version' => 1,
            'type' => 'standard',
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'renewal_type' => 'manual',
            'starts_at' => now()->subDays(20),
            'expires_at' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
            'base_amount' => 2999,
            'taxable_amount' => 2999,
            'tax_amount' => 539.82,
            'payable_amount' => 3538.82,
            'currency' => 'INR',
            'auto_renew' => false,
            'created_by' => $this->platformUserId ?: null,
            'updated_by' => $this->platformUserId ?: null,
        ], true);
        $this->seedRecord('subscription_versions', ['subscription_id' => $subscriptionId, 'version' => 1], [
            'plan_id' => $planId,
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addMonth(),
            'pricing_snapshot' => json_encode(['base_amount' => 2999]),
            'feature_snapshot' => json_encode(['users.limit' => 25]),
            'change_reason' => 'Initial seed.',
            'created_by' => $this->platformUserId ?: null,
            'created_at' => now(),
        ]);
        $platformInvoiceId = $this->seedRecord('platform_invoices', ['invoice_number' => 'PINV-DEMO-001'], [
            'tenant_id' => $this->tenantId,
            'subscription_id' => $subscriptionId,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 2999,
            'taxable_amount' => 2999,
            'tax_amount' => 539.82,
            'total_amount' => 3538.82,
            'paid_amount' => 3538.82,
            'balance_amount' => 0,
            'currency' => 'INR',
            'status' => 'paid',
            'pdf_file_id' => $ids['fileId'],
        ], true);
        $this->seedRecord('platform_invoice_items', ['platform_invoice_id' => $platformInvoiceId, 'item_type' => 'plan', 'description' => 'Growth monthly plan'], [
            'quantity' => 1,
            'unit_price' => 2999,
            'amount' => 2999,
            'metadata' => json_encode(['plan_id' => $planId]),
        ]);
        $platformPaymentId = $this->seedRecord('platform_payments', ['payment_number' => 'PPAY-DEMO-001'], [
            'tenant_id' => $this->tenantId,
            'platform_invoice_id' => $platformInvoiceId,
            'subscription_id' => $subscriptionId,
            'gateway' => 'razorpay',
            'gateway_payment_id' => 'pay_demo_001',
            'payment_method' => 'upi',
            'amount' => 3538.82,
            'currency' => 'INR',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'raw_response' => json_encode(['demo' => true]),
        ], true);
        $this->seedRecord('platform_refunds', ['refund_number' => 'PREF-DEMO-001'], [
            'tenant_id' => $this->tenantId,
            'platform_payment_id' => $platformPaymentId,
            'amount' => 100,
            'currency' => 'INR',
            'reason' => 'Demo adjustment',
            'status' => 'processed',
            'refunded_at' => now(),
        ], true);
        if ($addonPlanId) {
            $this->seedRecord('subscription_addons', ['subscription_id' => $subscriptionId, 'addon_plan_id' => $addonPlanId], [
                'quantity' => 1,
                'unit_price' => 499,
                'starts_at' => now(),
                'status' => 'active',
            ]);
        }
        if ($featureId) {
            $this->seedRecord('subscription_usage', ['tenant_id' => $this->tenantId, 'subscription_id' => $subscriptionId, 'feature_id' => $featureId, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString()], [
                'used_value' => 1,
                'limit_value' => 25,
            ]);
        }
        $couponId = $this->seedRecord('coupons', ['code' => 'DEMO10'], [
            'name' => 'Demo Ten Percent',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'max_redemptions' => 5,
            'status' => 'active',
        ], true);
        $this->seedPivot('coupon_plan_assignments', ['coupon_id' => $couponId, 'plan_id' => $planId]);
        $this->seedPivot('coupon_tenant_assignments', ['coupon_id' => $couponId, 'tenant_id' => $this->tenantId]);
        $this->seedRecord('coupon_redemptions', ['coupon_id' => $couponId, 'tenant_id' => $this->tenantId], [
            'subscription_id' => $subscriptionId,
            'platform_invoice_id' => $platformInvoiceId,
            'discount_amount' => 299.90,
            'redeemed_at' => now(),
        ]);
        $this->seedRecord('subscription_renewals', ['subscription_id' => $subscriptionId, 'status' => 'completed'], [
            'old_expires_at' => now()->subDay(),
            'new_expires_at' => now()->addMonth(),
            'amount' => 3538.82,
            'renewed_at' => now(),
        ]);

        $this->seedRecord('monitoring_services', ['code' => 'demo-api'], [
            'name' => 'Demo API',
            'service_type' => 'http',
            'status' => 'active',
            'check_interval_seconds' => 60,
        ]);
        $serviceId = (int) DB::table('monitoring_services')->where('code', 'demo-api')->value('id');
        $this->seedRecord('monitoring_service_logs', ['service_id' => $serviceId, 'message' => 'Demo health check passed.'], [
            'status' => 'up',
            'response_time_ms' => 120,
            'checked_at' => now(),
        ]);
        $this->seedRecord('tenant_usage_snapshots', ['tenant_id' => $this->tenantId, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString()], [
            'users_count' => 1,
            'storage_bytes' => 1024,
            'api_requests' => 100,
            'projects_count' => 1,
            'invoices_count' => 1,
        ]);
        $this->seedRecord('api_request_logs', ['tenant_id' => $this->tenantId, 'path' => '/api/tenant/dashboard', 'method' => 'GET'], [
            'user_id' => $this->ownerId,
            'status_code' => 200,
            'duration_ms' => 85,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
        $this->seedRecord('queue_job_logs', ['queue' => 'default', 'job_name' => 'DemoSeedJob'], [
            'status' => 'completed',
            'attempts' => 1,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
        ]);
        $this->seedRecord('scheduler_logs', ['command' => 'demo:heartbeat'], [
            'status' => 'completed',
            'output' => 'ok',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
        ]);
        $this->seedRecord('security_events', ['tenant_id' => $this->tenantId, 'user_id' => $this->ownerId, 'event' => 'demo.login'], [
            'severity' => 'info',
            'ip_address' => '127.0.0.1',
            'metadata' => json_encode(['seed' => true]),
            'created_at' => now(),
        ]);
        $incidentId = $this->seedRecord('system_incidents', ['title' => 'Demo Incident'], [
            'severity' => 'info',
            'status' => 'resolved',
            'started_at' => now()->subHour(),
            'resolved_at' => now()->subMinutes(30),
            'summary' => 'Seeded incident.',
            'resolution_notes' => Schema::hasColumn('system_incidents', 'resolution_notes') ? 'Resolved in seed data.' : null,
            'resolved_by' => Schema::hasColumn('system_incidents', 'resolved_by') ? $this->platformUserId : null,
        ]);
        $alertId = $this->seedRecord('monitoring_alerts', ['alertable_type' => 'system_incidents', 'alertable_id' => $incidentId, 'message' => 'Demo alert'], [
            'severity' => 'info',
            'status' => 'resolved',
            'triggered_at' => now()->subHour(),
            'resolved_at' => now()->subMinutes(30),
            'resolution_notes' => Schema::hasColumn('monitoring_alerts', 'resolution_notes') ? 'Demo resolved.' : null,
            'resolved_by' => Schema::hasColumn('monitoring_alerts', 'resolved_by') ? $this->platformUserId : null,
        ]);

        $providerId = $this->seedRecord('integration_providers', ['code' => 'demo-webhook'], [
            'name' => 'Demo Webhook',
            'category' => 'webhook',
            'auth_type' => 'secret',
            'status' => 'active',
            'metadata' => json_encode(['docs' => 'https://example.test']),
        ]);
        $integrationId = $this->seedRecord('tenant_integrations', ['tenant_id' => $this->tenantId, 'provider_id' => $providerId, 'name' => 'Demo Webhook'], [
            'status' => 'active',
            'connected_by' => $this->ownerId,
            'connected_at' => now(),
        ], true);
        $this->seedRecord('integration_credentials', ['tenant_integration_id' => $integrationId, 'key' => 'secret'], [
            'encrypted_value' => 'encrypted-demo-secret',
            'expires_at' => now()->addMonth(),
        ]);
        $webhookId = $this->seedRecord('integration_webhooks', ['tenant_integration_id' => $integrationId, 'event' => 'invoice.paid'], [
            'secret_hash' => hash('sha256', 'demo-secret'),
            'status' => 'active',
        ]);
        $this->seedRecord('integration_webhook_logs', ['webhook_id' => $webhookId, 'event' => 'invoice.paid'], [
            'payload' => json_encode(['invoice_id' => $ids['invoiceId']]),
            'status' => 'processed',
            'response_code' => 200,
            'received_at' => now(),
            'retry_count' => Schema::hasColumn('integration_webhook_logs', 'retry_count') ? 0 : null,
            'queued_at' => Schema::hasColumn('integration_webhook_logs', 'queued_at') ? now() : null,
        ]);
        $this->seedRecord('integration_sync_jobs', ['tenant_integration_id' => $integrationId, 'sync_type' => 'contacts'], [
            'direction' => 'outbound',
            'status' => 'completed',
            'started_at' => now()->subMinutes(3),
            'finished_at' => now()->subMinutes(2),
        ]);
        $this->seedRecord('integration_field_mappings', ['tenant_integration_id' => $integrationId, 'entity_type' => 'party', 'local_field' => 'display_name'], [
            'external_field' => 'name',
            'transform_rule' => json_encode(['trim' => true]),
        ]);
        $this->seedRecord('integration_rate_limits', ['tenant_integration_id' => $integrationId, 'window_start' => now()->startOfDay()], [
            'window_end' => now()->endOfHour(),
            'limit_count' => 1000,
            'used_count' => 12,
        ]);

        $notificationId = '11111111-1111-4111-8111-111111111111';
        DB::table('notifications')->updateOrInsert(['id' => $notificationId], [
            'tenant_id' => $this->tenantId,
            'type' => 'demo.notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->ownerId,
            'data' => json_encode(['message' => 'Demo notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedRecord('communication_logs', ['provider_message_id' => 'msg-demo-001'], [
            'uuid' => '22222222-2222-4222-8222-222222222222',
            'tenant_id' => $this->tenantId,
            'user_id' => $this->ownerId,
            'party_id' => $ids['clientPartyId'],
            'channel' => 'email',
            'direction' => 'outbound',
            'subject' => 'Welcome',
            'body' => 'Welcome to the seeded CRM workspace.',
            'provider' => 'smtp',
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_at' => now(),
            'metadata' => json_encode(['seed' => true]),
            'created_at' => now(),
        ]);
        $this->seedRecord('notification_templates', ['tenant_id' => $this->tenantId, 'code' => 'demo_welcome', 'channel' => 'email'], [
            'subject' => 'Welcome',
            'body' => 'Hello {{name}}',
            'variables' => json_encode(['name']),
            'status' => 'active',
        ], true);
        $backupRunId = $this->seedRecord('backup_runs', ['uuid' => '33333333-3333-4333-8333-333333333333'], [
            'backup_type' => 'full',
            'status' => 'completed',
            'file_id' => $ids['fileId'],
            'started_at' => now()->subHour(),
            'finished_at' => now()->subMinutes(50),
        ]);
        $tenantBackupRunId = $this->seedRecord('tenant_backup_runs', ['tenant_id' => $this->tenantId, 'backup_type' => 'tenant'], [
            'status' => 'completed',
            'file_id' => $ids['fileId'],
            'started_at' => now()->subHour(),
            'finished_at' => now()->subMinutes(45),
        ], true);
        $this->seedRecord('tenant_restore_requests', ['tenant_id' => $this->tenantId, 'tenant_backup_run_id' => $tenantBackupRunId], [
            'status' => 'pending',
            'requested_by' => $this->ownerId,
            'approved_by' => $this->platformUserId ?: null,
            'requested_at' => now(),
            'remarks' => 'Seeded restore request.',
        ], true);

        $ticketId = $this->seedRecord('platform_tickets', ['ticket_number' => 'PTICK-DEMO-001'], [
            'tenant_id' => $this->tenantId,
            'subject' => 'Demo support ticket',
            'description' => 'Seeded support ticket.',
            'priority' => 'medium',
            'status' => 'open',
            'assigned_to' => $this->platformUserId ?: null,
            'category' => Schema::hasColumn('platform_tickets', 'category') ? 'onboarding' : null,
            'source' => Schema::hasColumn('platform_tickets', 'source') ? 'seed' : null,
            'opened_at' => Schema::hasColumn('platform_tickets', 'opened_at') ? now() : null,
        ], true);
        $this->seedRecord('platform_ticket_comments', ['platform_ticket_id' => $ticketId, 'comment' => 'Seeded ticket comment.'], [
            'platform_user_id' => $this->platformUserId ?: null,
            'user_id' => $this->ownerId,
            'is_internal' => false,
        ]);
        $this->seedRecord('platform_ticket_attachments', ['platform_ticket_id' => $ticketId, 'file_id' => $ids['fileId']], [
            'created_by' => $this->platformUserId ?: null,
        ]);
        $kbCategoryId = $this->seedRecord('knowledge_base_categories', ['slug' => 'demo-help'], [
            'name' => 'Demo Help',
            'parent_id' => null,
            'audience' => 'all',
            'status' => 'active',
        ], true);
        $this->seedRecord('knowledge_base_articles', ['slug' => 'demo-getting-started'], [
            'category_id' => $kbCategoryId,
            'title' => 'Demo Getting Started',
            'body' => 'Seeded knowledge base article.',
            'audience' => 'all',
            'status' => 'published',
            'created_by' => $this->platformUserId ?: null,
            'published_at' => now(),
        ], true);
        if ($this->platformUserId) {
            $this->seedRecord('remote_login_sessions', ['tenant_id' => $this->tenantId, 'platform_user_id' => $this->platformUserId, 'reason' => 'Demo support access'], [
                'impersonated_user_id' => Schema::hasColumn('remote_login_sessions', 'impersonated_user_id') ? $this->ownerId : null,
                'target_user_id' => Schema::hasColumn('remote_login_sessions', 'target_user_id') ? $this->ownerId : null,
                'duration_minutes' => Schema::hasColumn('remote_login_sessions', 'duration_minutes') ? 30 : null,
                'expires_at' => Schema::hasColumn('remote_login_sessions', 'expires_at') ? now()->addMinutes(30) : null,
                'status' => 'active',
                'started_at' => Schema::hasColumn('remote_login_sessions', 'started_at') ? now() : null,
                'ip_address' => '127.0.0.1',
                'user_agent' => Schema::hasColumn('remote_login_sessions', 'user_agent') ? 'Demo Seeder' : null,
            ], true);
        }
        $this->seedRecord('tenant_api_tokens', ['token_hash' => hash('sha256', 'demo-tenant-token')], [
            'tenant_id' => $this->tenantId,
            'name' => 'Demo Tenant Token',
            'encrypted_token_preview' => 'ten_demo_********',
            'abilities' => json_encode(['dashboard.view']),
            'last_used_at' => now()->subHour(),
            'expires_at' => now()->addMonth(),
            'created_by' => $this->ownerId,
        ], true);
        $this->seedRecord('report_export_jobs', ['report_code' => 'tenant_summary', 'created_by' => $this->platformUserId ?: null], [
            'format' => 'csv',
            'filters' => json_encode(['tenant_id' => $this->tenantId]),
            'status' => 'completed',
            'file_id' => $ids['fileId'],
        ], true);
        $this->seedRecord('backup_settings', ['key' => 'demo_retention_days'], [
            'value' => json_encode(7),
            'updated_by' => $this->platformUserId ?: null,
        ]);
        $this->seedRecord('onboarding_checklists', ['step_code' => 'demo_profile'], [
            'title' => 'Complete Profile',
            'description' => 'Seeded onboarding step.',
            'sort_order' => 1,
            'status' => 'active',
        ], true);
        $this->seedRecord('tenant_onboarding_steps', ['tenant_id' => $this->tenantId, 'step_code' => 'demo_profile'], [
            'status' => 'completed',
            'metadata' => json_encode(['completed_by' => $this->ownerId]),
            'updated_by' => $this->platformUserId ?: null,
        ]);
        $legalDocumentId = $this->seedRecord('legal_documents', ['document_type' => 'terms', 'version' => 'demo-1.0'], [
            'title' => 'Demo Terms',
            'content' => 'Seeded legal document.',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->platformUserId ?: null,
        ], true);
        $this->seedRecord('tenant_legal_acceptances', ['tenant_id' => $this->tenantId, 'legal_document_id' => $legalDocumentId], [
            'user_id' => $this->ownerId,
            'accepted_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);
        $this->seedRecord('platform_announcements', ['title' => 'Demo Announcement'], [
            'body' => 'Seeded platform announcement.',
            'audience' => 'all',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->platformUserId ?: null,
        ], true);
        $endpointId = $this->seedRecord('platform_webhook_endpoints', ['name' => 'Demo Endpoint', 'url' => 'https://example.test/webhook'], [
            'tenant_id' => $this->tenantId,
            'events' => json_encode(['invoice.paid']),
            'secret_hash' => hash('sha256', 'demo-platform-webhook'),
            'status' => 'active',
        ], true);
        $this->seedRecord('platform_webhook_deliveries', ['platform_webhook_endpoint_id' => $endpointId, 'event' => 'invoice.paid'], [
            'payload' => json_encode(['invoice_id' => $platformInvoiceId]),
            'status' => 'delivered',
            'response_code' => 200,
            'retry_count' => 0,
            'queued_at' => now(),
        ], true);
        $this->seedRecord('tenant_import_export_jobs', ['tenant_id' => $this->tenantId, 'user_id' => $this->ownerId, 'type' => 'export', 'module' => 'clients'], [
            'status' => 'completed',
            'payload' => json_encode(['format' => 'csv']),
            'result' => json_encode(['file_id' => $ids['fileId']]),
            'started_at' => now()->subMinutes(6),
            'finished_at' => now()->subMinutes(5),
        ], true);

        $this->seedRecord('activity_logs', ['tenant_id' => $this->tenantId, 'subject_type' => 'tenants', 'subject_id' => $this->tenantId, 'event' => 'seeded'], [
            'actor_user_id' => $this->ownerId,
            'actor_platform_user_id' => $this->platformUserId ?: null,
            'description' => 'Demo relational seed completed.',
            'old_values' => null,
            'new_values' => json_encode(['backup_run_id' => $backupRunId, 'alert_id' => $alertId]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'DemoRelationalDataSeeder',
            'created_at' => now(),
        ]);
    }

    private function lookupId(string $group, string $code): ?int
    {
        $id = DB::table('tenant_lookups')
            ->whereNull('tenant_id')
            ->where('group', $group)
            ->where('code', $code)
            ->value('id');

        return $id ? (int) $id : null;
    }
}
