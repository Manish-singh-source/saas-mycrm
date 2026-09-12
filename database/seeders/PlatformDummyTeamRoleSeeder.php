<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformDummyTeamRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $roles = [["Team Lead", "PL-TEAM-ROLE-LEAD", ["team.view", "team.manage", "member.assign", "assignment.manage", "report.view"], 1, true], ["Assistant Lead", "PL-TEAM-ROLE-ASSISTANT", ["team.view", "member.assign", "assignment.manage", "report.view"], 2, true], ["Coordinator", "PL-TEAM-ROLE-COORDINATOR", ["team.view", "assignment.manage", "report.view"], 3, false], ["Reviewer", "PL-TEAM-ROLE-REVIEWER", ["team.view", "assignment.review", "report.view"], 4, false], ["Member", "PL-TEAM-ROLE-MEMBER", ["team.view", "assignment.work"], 5, true], ["Observer", "PL-TEAM-ROLE-OBSERVER", ["team.view", "report.view"], 6, false]];
        DB::table("platform_team_roles")->upsert(array_map(fn ($r) => ["uuid" => (string) Str::uuid(), "name" => $r[0], "code" => $r[1], "description" => $r[0]." team role for platform testing.", "permissions" => json_encode($r[2]), "sort_order" => $r[3], "is_system" => $r[4], "status" => "active", "deleted_at" => null, "created_at" => $now, "updated_at" => $now], $roles), ["code"], ["name", "description", "permissions", "sort_order", "is_system", "status", "deleted_at", "updated_at"]);
    }
}