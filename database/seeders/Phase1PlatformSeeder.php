<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Database\Seeders\Concerns\SeedsRecords;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Phase1PlatformSeeder extends Seeder
{
    use SeedsRecords;

    public function run(): void
    {
        $roleIds = DB::table('platform_roles')->pluck('id', 'name')->all();
        $permissionNames = DB::table('platform_permissions')->pluck('name')->all();

        $userIds = $this->seedPlatformUsers($roleIds);
        $teamRoleIds = $this->seedTeamRoles($permissionNames);
        $teamIds = $this->seedTeams($userIds);
        $this->seedTeamMembers($teamIds, $teamRoleIds, $userIds);
        $this->seedTeamAssignments($teamIds, $userIds);
        $this->seedActivityLogs($userIds, $teamRoleIds, $teamIds);
    }

    /**
     * @param  array<string, int>  $roleIds
     * @return array<string, int>
     */
    private function seedPlatformUsers(array $roleIds): array
    {
        $users = [
            ['SA-0001', 'Super', 'Admin', 'support@technofra.com', 'Chief Platform Administrator', 'Platform', 'super_admin'],
            ['PF-0002', 'Aarav', 'Mehta', 'aarav.mehta.platform@example.test', 'Platform Administrator', 'Platform', 'admin'],
            ['PF-0003', 'Isha', 'Patel', 'isha.patel.platform@example.test', 'Billing Manager', 'Billing', 'billing_manager'],
            ['PF-0004', 'Kabir', 'Shah', 'kabir.shah.platform@example.test', 'Support Manager', 'Support', 'support_manager'],
            ['PF-0005', 'Meera', 'Rao', 'meera.rao.platform@example.test', 'Operations Manager', 'Operations', 'operations_manager'],
            ['PF-0006', 'Rohan', 'Iyer', 'rohan.iyer.platform@example.test', 'Tenant Success Lead', 'Customer Success', 'support_manager'],
            ['PF-0007', 'Nisha', 'Kapoor', 'nisha.kapoor.platform@example.test', 'Billing Analyst', 'Billing', 'billing_manager'],
            ['PF-0008', 'Dev', 'Malhotra', 'dev.malhotra.platform@example.test', 'Integration Specialist', 'Integrations', 'operations_manager'],
            ['PF-0009', 'Tara', 'Joshi', 'tara.joshi.platform@example.test', 'Support Engineer', 'Support', 'support_manager'],
            ['PF-0010', 'Vikram', 'Nair', 'vikram.nair.platform@example.test', 'Infrastructure Lead', 'Operations', 'operations_manager'],
            ['PF-0011', 'Anika', 'Reddy', 'anika.reddy.platform@example.test', 'QA Analyst', 'Platform', 'admin'],
            ['PF-0012', 'Sahil', 'Khan', 'sahil.khan.platform@example.test', 'Security Analyst', 'Operations', 'operations_manager'],
            ['PF-0013', 'Pooja', 'Desai', 'pooja.desai.platform@example.test', 'Account Specialist', 'Billing', 'billing_manager'],
            ['PF-0014', 'Arjun', 'Sinha', 'arjun.sinha.platform@example.test', 'Knowledge Base Editor', 'Support', 'support_manager'],
            ['PF-0015', 'Neha', 'Bose', 'neha.bose.platform@example.test', 'Product Operations', 'Platform', 'admin'],
            ['PF-0016', 'Kunal', 'Trivedi', 'kunal.trivedi.platform@example.test', 'Tenant Onboarding Lead', 'Customer Success', 'support_manager'],
            ['PF-0017', 'Riya', 'Menon', 'riya.menon.platform@example.test', 'Monitoring Analyst', 'Operations', 'operations_manager'],
            ['PF-0018', 'Harsh', 'Vora', 'harsh.vora.platform@example.test', 'Collections Specialist', 'Billing', 'billing_manager'],
            ['PF-0019', 'Simran', 'Gill', 'simran.gill.platform@example.test', 'Escalation Specialist', 'Support', 'support_manager'],
            ['PF-0020', 'Manav', 'Bhat', 'manav.bhat.platform@example.test', 'Release Coordinator', 'Platform', 'admin'],
        ];

        $userIds = [];
        foreach ($users as [$code, $firstName, $lastName, $email, $designation, $department, $role]) {
            $userId = $this->seedRecord('platform_users', ['email' => $email], [
                'employee_code' => $code,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'display_name' => $firstName.' '.$lastName,
                'mobile' => '+919900'.substr(str_replace('PF-', '', $code), -4),
                'password' => Hash::make('Password@123'),
                'designation' => $designation,
                'department' => $department,
                'timezone' => 'Asia/Kolkata',
                'locale' => 'en',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
                'last_login_at' => now()->subDays(random_int(1, 12)),
                'last_login_ip' => '127.0.0.1',
                'status' => 'active',
            ], true);

            $userIds[$code] = $userId;

            if (isset($roleIds[$role])) {
                $this->seedPivot('platform_model_has_roles', [
                    'role_id' => $roleIds[$role],
                    'model_id' => $userId,
                    'model_type' => PlatformUser::class,
                ]);
            }
        }

        return $userIds;
    }

    /**
     * @param  list<string>  $permissionNames
     * @return array<string, int>
     */
    private function seedTeamRoles(array $permissionNames): array
    {
        $roles = [
            'platform-team-lead' => ['Platform Team Lead', ['platform_user.view', 'platform_team.', 'tenant.view', 'report.view']],
            'billing-owner' => ['Billing Owner', ['billing.', 'subscription.', 'plan.', 'feature.', 'coupon.', 'report.view']],
            'support-owner' => ['Support Owner', ['support.', 'tenant.view', 'tenant.impersonate', 'audit_log.view']],
            'operations-owner' => ['Operations Owner', ['monitoring.', 'integration.', 'setting.', 'audit_log.', 'report.view']],
            'team-member' => ['Team Member', ['dashboard.view', 'tenant.view', 'support.ticket.view', 'report.view']],
        ];

        $teamRoleIds = [];
        foreach ($roles as $code => [$name, $patterns]) {
            $attachedPermissions = array_values(array_filter($permissionNames, function (string $permission) use ($patterns): bool {
                foreach ($patterns as $pattern) {
                    if ($permission === $pattern || str_starts_with($permission, $pattern)) {
                        return true;
                    }
                }

                return false;
            }));

            $teamRoleIds[$code] = $this->seedRecord('platform_team_roles', ['code' => $code], [
                'name' => $name,
                'description' => $name.' responsibilities for platform phase 1 teams.',
                'permissions' => json_encode($attachedPermissions),
                'sort_order' => count($teamRoleIds) + 1,
                'is_system' => true,
                'status' => 'active',
            ], true);
        }

        return $teamRoleIds;
    }

    /**
     * @param  array<string, int>  $userIds
     * @return array<string, int>
     */
    private function seedTeams(array $userIds): array
    {
        $teams = [
            'platform-core' => ['Platform Core', 'Core platform administration team.', 'PF-0002', 'PF-0015', 'platform@example.test', '#1D4ED8', 'shield-check'],
            'billing-ops' => ['Billing Operations', 'Subscriptions, invoices, payments, and refunds.', 'PF-0003', 'PF-0013', 'billing@example.test', '#047857', 'receipt'],
            'support-success' => ['Support Success', 'Support, onboarding, tickets, and knowledge base.', 'PF-0004', 'PF-0016', 'support@example.test', '#B45309', 'life-buoy'],
            'platform-operations' => ['Platform Operations', 'Monitoring, integrations, security, and settings.', 'PF-0005', 'PF-0017', 'ops@example.test', '#7C3AED', 'activity'],
            'release-quality' => ['Release Quality', 'Release coordination and quality checks.', 'PF-0011', 'PF-0020', 'quality@example.test', '#BE123C', 'badge-check'],
        ];

        $teamIds = [];
        foreach ($teams as $code => [$name, $description, $leadCode, $assistantCode, $email, $color, $icon]) {
            $teamIds[$code] = $this->seedRecord('platform_teams', ['code' => $code], [
                'name' => $name,
                'description' => $description,
                'lead_platform_user_id' => $userIds[$leadCode] ?? null,
                'assistant_lead_platform_user_id' => $userIds[$assistantCode] ?? null,
                'email' => $email,
                'phone' => '+919988'.str_pad((string) (count($teamIds) + 1), 6, '0', STR_PAD_LEFT),
                'color' => $color,
                'icon' => $icon,
                'visibility' => 'internal',
                'status' => 'active',
            ], true);
        }

        return $teamIds;
    }

    /**
     * @param  array<string, int>  $teamIds
     * @param  array<string, int>  $teamRoleIds
     * @param  array<string, int>  $userIds
     */
    private function seedTeamMembers(array $teamIds, array $teamRoleIds, array $userIds): void
    {
        $members = [
            ['platform-core', 'PF-0002', 'platform-team-lead'], ['platform-core', 'PF-0011', 'team-member'], ['platform-core', 'PF-0015', 'team-member'], ['platform-core', 'PF-0020', 'team-member'],
            ['billing-ops', 'PF-0003', 'billing-owner'], ['billing-ops', 'PF-0007', 'team-member'], ['billing-ops', 'PF-0013', 'team-member'], ['billing-ops', 'PF-0018', 'team-member'],
            ['support-success', 'PF-0004', 'support-owner'], ['support-success', 'PF-0006', 'team-member'], ['support-success', 'PF-0009', 'team-member'], ['support-success', 'PF-0014', 'team-member'], ['support-success', 'PF-0016', 'team-member'], ['support-success', 'PF-0019', 'team-member'],
            ['platform-operations', 'PF-0005', 'operations-owner'], ['platform-operations', 'PF-0008', 'team-member'], ['platform-operations', 'PF-0010', 'team-member'], ['platform-operations', 'PF-0012', 'team-member'], ['platform-operations', 'PF-0017', 'team-member'],
            ['release-quality', 'PF-0011', 'platform-team-lead'], ['release-quality', 'PF-0015', 'team-member'], ['release-quality', 'PF-0020', 'team-member'],
        ];

        foreach ($members as [$teamCode, $userCode, $roleCode]) {
            $this->seedRecord('platform_team_members', [
                'platform_team_id' => $teamIds[$teamCode],
                'platform_user_id' => $userIds[$userCode],
            ], [
                'platform_team_role_id' => $teamRoleIds[$roleCode],
                'joined_at' => now()->subDays(30)->toDateString(),
                'left_at' => null,
                'status' => 'active',
            ]);
        }
    }

    /** @param  array<string, int>  $teamIds @param  array<string, int>  $userIds */
    private function seedTeamAssignments(array $teamIds, array $userIds): void
    {
        $assignments = [
            ['platform-core', 'platform_modules', 1, 'module_owner', 'PF-0002'],
            ['billing-ops', 'billing_catalog', 1, 'billing_owner', 'PF-0003'],
            ['support-success', 'support_queue', 1, 'support_owner', 'PF-0004'],
            ['platform-operations', 'monitoring_services', 1, 'operations_owner', 'PF-0005'],
            ['release-quality', 'release_train', 1, 'quality_owner', 'PF-0011'],
        ];

        foreach ($assignments as [$teamCode, $type, $id, $role, $assignedBy]) {
            $this->seedRecord('platform_team_assignments', [
                'platform_team_id' => $teamIds[$teamCode],
                'assignable_type' => $type,
                'assignable_id' => $id,
            ], [
                'assignment_role' => $role,
                'assigned_by' => $userIds[$assignedBy] ?? null,
                'assigned_at' => now()->subDays(15),
                'released_at' => null,
                'status' => 'active',
            ]);
        }
    }

    /**
     * @param  array<string, int>  $userIds
     * @param  array<string, int>  $teamRoleIds
     * @param  array<string, int>  $teamIds
     */
    private function seedActivityLogs(array $userIds, array $teamRoleIds, array $teamIds): void
    {
        $actorId = $userIds['SA-0001'] ?? DB::table('platform_users')->where('email', 'support@technofra.com')->value('id');
        $subjects = [
            ['platform_permissions', (int) DB::table('platform_permissions')->where('name', 'dashboard.view')->value('id'), 'Created platform permissions.'],
            ['platform_roles', (int) DB::table('platform_roles')->where('name', 'super_admin')->value('id'), 'Created platform roles and attached permissions.'],
            ['platform_users', $actorId, 'Created phase 1 platform users with profile information.'],
            ['platform_team_roles', reset($teamRoleIds), 'Created platform team roles with permission payloads.'],
            ['platform_teams', reset($teamIds), 'Created phase 1 platform teams.'],
            ['platform_team_members', (int) DB::table('platform_team_members')->value('id'), 'Attached members to phase 1 platform teams.'],
            ['platform_team_assignments', (int) DB::table('platform_team_assignments')->value('id'), 'Assigned platform teams to operational ownership areas.'],
        ];

        foreach ($subjects as [$type, $id, $description]) {
            if (! $id) {
                continue;
            }

            $this->seedRecord('activity_logs', [
                'tenant_id' => null,
                'subject_type' => $type,
                'subject_id' => (int) $id,
                'event' => 'phase1.seeded',
            ], [
                'actor_platform_user_id' => $actorId ?: null,
                'description' => $description,
                'new_values' => json_encode(['phase' => 1, 'table' => $type]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Phase1PlatformSeeder',
                'created_at' => now(),
            ]);
        }
    }
}
