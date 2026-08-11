<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTeamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_list_supports_search_filters_and_sorting(): void
    {
        Sanctum::actingAs($this->platformUser(), ['platform:*']);

        $alpha = $this->team(['name' => 'Alpha Support', 'code' => 'ALPHA_SUPPORT', 'visibility' => 'internal']);
        $this->team(['name' => 'Beta Private', 'code' => 'BETA_PRIVATE', 'visibility' => 'private', 'status' => 'inactive']);

        $this->getJson('/api/platform/v1/platform-teams?search=Alpha&filter[status]=active&filter[visibility]=internal&sort=name&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $alpha->uuid)
            ->assertJsonCount(1, 'data');
    }

    public function test_team_create_update_member_and_assignment_flow(): void
    {
        $actor = $this->platformUser();
        $member = $this->platformUser(['email' => 'member@example.test']);
        Sanctum::actingAs($actor, ['platform:*']);

        $role = $this->teamRole(['name' => 'Support Owner', 'code' => 'support_owner']);

        $created = $this->postJson('/api/platform/v1/platform-teams', [
            'name' => 'Customer Ops',
            'code' => 'CUSTOMER_OPS',
            'description' => 'Customer operations team',
            'lead_platform_user_id' => $actor->id,
            'assistant_lead_platform_user_id' => $member->id,
            'email' => 'ops@example.test',
            'phone' => '+911234567890',
            'color' => '#2563eb',
            'icon' => 'users',
            'visibility' => 'private',
            'status' => 'active',
        ])->assertCreated()->json('data.team');

        $this->patchJson('/api/platform/v1/platform-teams/'.$created['uuid'], [
            'name' => 'Customer Operations',
            'visibility' => 'internal',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('data.team.visibility', 'internal');

        $this->postJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/members', [
            'platform_user_uuid' => $member->uuid,
            'team_role_uuid' => $role->uuid,
            'joined_at' => '2026-08-11',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.member_added', true);

        $members = $this->getJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/members')
            ->assertOk()
            ->assertJsonPath('data.members.0.user_uuid', $member->uuid)
            ->json('data.members');

        $this->patchJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/members/'.$members[0]['id'], [
            'team_role_uuid' => null,
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('data.member.status', 'inactive');

        $assignment = $this->postJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/assignments', [
            'assignable_type' => 'tenant',
            'assignable_id' => 123,
            'assignment_role' => 'support_owner',
        ])->assertCreated()->json('data.assignment');

        $this->deleteJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/assignments/'.$assignment['id'])
            ->assertOk();

        $this->deleteJson('/api/platform/v1/platform-teams/'.$created['uuid'].'/members/'.$members[0]['id'], [
            'audit_reason' => 'test cleanup',
        ])->assertOk();
    }

    public function test_team_roles_support_paginated_list_detail_update_and_delete_guards(): void
    {
        $user = $this->platformUser();
        Sanctum::actingAs($user, ['platform:*']);

        $role = $this->teamRole(['name' => 'Escalation Lead', 'code' => 'escalation_lead', 'sort_order' => 5]);

        $this->getJson('/api/platform/v1/platform-team-roles?search=Escalation&filter[status]=active&sort=sort_order&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $role->uuid)
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/platform/v1/platform-team-roles/'.$role->uuid)
            ->assertOk()
            ->assertJsonPath('data.team_role.uuid', $role->uuid);

        $this->patchJson('/api/platform/v1/platform-team-roles/'.$role->uuid, [
            'name' => 'Escalation Lead Updated',
            'description' => 'Owns escalations',
            'permissions' => ['tickets' => ['view', 'assign']],
            'sort_order' => 10,
            'is_system' => false,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('data.team_role.name', 'Escalation Lead Updated');

        $team = $this->team(['name' => 'Assigned Team', 'code' => 'ASSIGNED_TEAM']);
        DB::table('platform_team_members')->insert([
            'platform_team_id' => $team->id,
            'platform_user_id' => $user->id,
            'platform_team_role_id' => $role->id,
            'joined_at' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson('/api/platform/v1/platform-team-roles/'.$role->uuid, ['audit_reason' => 'test guard'])
            ->assertStatus(409)
            ->assertJsonPath('errors.code', 'TEAM_ROLE_IN_USE');

        DB::table('platform_team_members')->where('platform_team_role_id', $role->id)->delete();
        $this->deleteJson('/api/platform/v1/platform-team-roles/'.$role->uuid, ['audit_reason' => 'test cleanup'])
            ->assertOk();
    }

    public function test_team_and_team_role_routes_require_platform_permissions(): void
    {
        Sanctum::actingAs($this->platformUser(), []);

        $this->getJson('/api/platform/v1/platform-teams')->assertForbidden();
        $this->getJson('/api/platform/v1/platform-team-roles')->assertForbidden();
    }

    private function team(array $attributes = []): object
    {
        $id = DB::table('platform_teams')->insertGetId(array_merge([
            'uuid' => (string) str()->uuid(),
            'name' => 'Team '.str()->random(6),
            'code' => 'TEAM_'.str()->upper(str()->random(6)),
            'description' => 'Test platform team',
            'lead_platform_user_id' => null,
            'assistant_lead_platform_user_id' => null,
            'email' => null,
            'phone' => null,
            'color' => '#2563eb',
            'icon' => 'users',
            'visibility' => 'internal',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('platform_teams')->where('id', $id)->first();
    }

    private function teamRole(array $attributes = []): object
    {
        $id = DB::table('platform_team_roles')->insertGetId(array_merge([
            'uuid' => (string) str()->uuid(),
            'name' => 'Team Role '.str()->random(6),
            'code' => 'team_role_'.str()->lower(str()->random(6)),
            'description' => 'Test team role',
            'permissions' => json_encode(['teams' => ['view']]),
            'sort_order' => 0,
            'is_system' => false,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return DB::table('platform_team_roles')->where('id', $id)->first();
    }

    private function platformUser(array $attributes = []): PlatformUser
    {
        return PlatformUser::query()->create(array_merge([
            'uuid' => (string) str()->uuid(),
            'employee_code' => 'TEAM-'.str()->upper(str()->random(6)),
            'first_name' => 'Team',
            'last_name' => 'Tester',
            'display_name' => 'Team Tester',
            'email' => str()->random(10).'@example.test',
            'password' => 'Password@123',
            'status' => 'active',
            'email_verified_at' => now(),
        ], $attributes));
    }
}