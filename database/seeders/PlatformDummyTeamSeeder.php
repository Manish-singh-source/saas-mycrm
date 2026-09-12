<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformDummyTeamSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $departments = DB::table('platform_departments')->pluck('id', 'code')->all();
            $users = DB::table('platform_users')->pluck('id', 'email')->all();
            $teams = [
                ['Enterprise Onboarding Squad', 'PL-TEAM-ONBOARDING', 'PL-DEPT-CS', 'Handles new enterprise tenant setup, data handoff, and launch readiness.', 'nisha.rao.platform@example.com', 'ananya.sen.platform@example.com', 'onboarding.platform@example.com', '+911204440101', '#2563EB', 'rocket', 'internal'],
                ['Priority Support Desk', 'PL-TEAM-PRIORITY-SUPPORT', 'PL-DEPT-SUPPORT', 'Monitors premium support queues and critical tenant escalations.', 'kabir.kapoor.platform@example.com', 'ishita.bose.platform@example.com', 'priority.support.platform@example.com', '+911204440102', '#DC2626', 'life-buoy', 'internal'],
                ['Billing Assurance Cell', 'PL-TEAM-BILLING-ASSURANCE', 'PL-DEPT-FIN', 'Tests invoice flows, payment exceptions, refunds, and subscription changes.', 'priya.sharma.platform@example.com', 'sneha.patel.platform@example.com', 'billing.assurance.platform@example.com', '+911204440103', '#059669', 'receipt', 'private'],
                ['Platform Reliability Pod', 'PL-TEAM-RELIABILITY', 'PL-DEPT-ENG', 'Reviews monitoring, incidents, API health, and release readiness.', 'rohan.iyer.platform@example.com', null, 'reliability.platform@example.com', '+911204440104', '#7C3AED', 'activity', 'internal'],
                ['Compliance Review Group', 'PL-TEAM-COMPLIANCE-REVIEW', 'PL-DEPT-COMP', 'Performs audit reviews, evidence checks, and access policy validation.', null, null, 'compliance.review.platform@example.com', '+911204440105', '#475569', 'shield-check', 'private'],
                ['Content Enablement Crew', 'PL-TEAM-CONTENT-ENABLEMENT', 'PL-DEPT-CONTENT', 'Maintains help articles, announcements, release notes, and training material.', 'tara.dutta.platform@example.com', null, 'content.enablement.platform@example.com', '+911204440106', '#EA580C', 'book-open', 'internal'],
                ['Growth Handoff Team', 'PL-TEAM-GROWTH-HANDOFF', 'PL-DEPT-SALES', 'Coordinates commercial-to-success handoffs and tenant expansion opportunities.', null, 'meera.nair.platform@example.com', 'growth.handoff.platform@example.com', '+911204440107', '#0891B2', 'handshake', 'internal'],
            ];

            foreach ($teams as $team) {
                DB::table('platform_teams')->upsert([[
                    'uuid' => (string) Str::uuid(),
                    'name' => $team[0],
                    'code' => $team[1],
                    'platform_department_id' => $departments[$team[2]] ?? null,
                    'description' => $team[3],
                    'lead_platform_user_id' => $team[4] ? ($users[$team[4]] ?? null) : null,
                    'assistant_lead_platform_user_id' => $team[5] ? ($users[$team[5]] ?? null) : null,
                    'email' => $team[6],
                    'phone' => $team[7],
                    'color' => $team[8],
                    'icon' => $team[9],
                    'visibility' => $team[10],
                    'status' => 'active',
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]], ['code'], ['name', 'platform_department_id', 'description', 'lead_platform_user_id', 'assistant_lead_platform_user_id', 'email', 'phone', 'color', 'icon', 'visibility', 'status', 'deleted_at', 'updated_at']);
            }

            $teamIds = DB::table('platform_teams')->whereIn('code', array_column($teams, 1))->pluck('id', 'code')->all();
            $teamRoleIds = DB::table('platform_team_roles')->pluck('id', 'code')->all();
            $members = [
                ['PL-TEAM-ONBOARDING', 'nisha.rao.platform@example.com', 'PL-TEAM-ROLE-LEAD'],
                ['PL-TEAM-ONBOARDING', 'ananya.sen.platform@example.com', 'PL-TEAM-ROLE-ASSISTANT'],
                ['PL-TEAM-ONBOARDING', 'vikram.sethi.platform@example.com', 'PL-TEAM-ROLE-MEMBER'],
                ['PL-TEAM-ONBOARDING', 'meera.nair.platform@example.com', 'PL-TEAM-ROLE-COORDINATOR'],
                ['PL-TEAM-PRIORITY-SUPPORT', 'kabir.kapoor.platform@example.com', 'PL-TEAM-ROLE-LEAD'],
                ['PL-TEAM-PRIORITY-SUPPORT', 'ishita.bose.platform@example.com', 'PL-TEAM-ROLE-ASSISTANT'],
                ['PL-TEAM-PRIORITY-SUPPORT', 'farhan.khan.platform@example.com', 'PL-TEAM-ROLE-MEMBER'],
                ['PL-TEAM-PRIORITY-SUPPORT', 'arjun.menon.platform@example.com', 'PL-TEAM-ROLE-REVIEWER'],
                ['PL-TEAM-BILLING-ASSURANCE', 'priya.sharma.platform@example.com', 'PL-TEAM-ROLE-LEAD'],
                ['PL-TEAM-BILLING-ASSURANCE', 'sneha.patel.platform@example.com', 'PL-TEAM-ROLE-ASSISTANT'],
                ['PL-TEAM-BILLING-ASSURANCE', 'dev.malhotra.platform@example.com', 'PL-TEAM-ROLE-OBSERVER'],
                ['PL-TEAM-RELIABILITY', 'rohan.iyer.platform@example.com', 'PL-TEAM-ROLE-LEAD'],
                ['PL-TEAM-RELIABILITY', 'arjun.menon.platform@example.com', 'PL-TEAM-ROLE-COORDINATOR'],
                ['PL-TEAM-RELIABILITY', 'aarav.mehta.platform@example.com', 'PL-TEAM-ROLE-OBSERVER'],
                ['PL-TEAM-COMPLIANCE-REVIEW', 'dev.malhotra.platform@example.com', 'PL-TEAM-ROLE-REVIEWER'],
                ['PL-TEAM-COMPLIANCE-REVIEW', 'arjun.menon.platform@example.com', 'PL-TEAM-ROLE-MEMBER'],
                ['PL-TEAM-COMPLIANCE-REVIEW', 'priya.sharma.platform@example.com', 'PL-TEAM-ROLE-OBSERVER'],
                ['PL-TEAM-CONTENT-ENABLEMENT', 'tara.dutta.platform@example.com', 'PL-TEAM-ROLE-LEAD'],
                ['PL-TEAM-CONTENT-ENABLEMENT', 'ananya.sen.platform@example.com', 'PL-TEAM-ROLE-REVIEWER'],
                ['PL-TEAM-GROWTH-HANDOFF', 'meera.nair.platform@example.com', 'PL-TEAM-ROLE-ASSISTANT'],
                ['PL-TEAM-GROWTH-HANDOFF', 'vikram.sethi.platform@example.com', 'PL-TEAM-ROLE-MEMBER'],
            ];

            $rows = [];
            foreach ($members as $member) {
                if (isset($teamIds[$member[0]], $users[$member[1]])) {
                    $rows[] = [
                        'platform_team_id' => $teamIds[$member[0]],
                        'platform_user_id' => $users[$member[1]],
                        'platform_team_role_id' => $teamRoleIds[$member[2]] ?? null,
                        'joined_at' => '2026-01-15',
                        'left_at' => null,
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('platform_team_members')->upsert($rows, ['platform_team_id', 'platform_user_id'], ['platform_team_role_id', 'joined_at', 'left_at', 'status', 'updated_at']);
        });
    }
}
