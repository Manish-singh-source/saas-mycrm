<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformTeamController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function index(Request $request)
    {
        $q = DB::table('platform_teams')->whereNull('deleted_at');
        if ($request->filled('search')) $q->where(fn ($x) => $x->where('name', 'like', '%'.$request->string('search').'%')->orWhere('code', 'like', '%'.$request->string('search').'%'));
        if ($request->filled('filter.status')) $q->where('status', $request->input('filter.status'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function store(Request $r)
    {
        $d = $this->data($r);
        $id = DB::table('platform_teams')->insertGetId(['uuid' => (string) Str::uuid(), ...$d, 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        $team = DB::table('platform_teams')->where('id', $id)->first();
        $this->admin->audit($r, 'platform_team_created', 'platform_team', $id, null, (array) $team);
        return $this->success(['team' => $team], 'Platform team created.', 201);
    }

    public function show(string $team_uuid)
    {
        $team = DB::table('platform_teams')->where('uuid', $team_uuid)->whereNull('deleted_at')->first();
        abort_if(! $team, 404);
        return $this->success(['team' => $team, 'members_count' => DB::table('platform_team_members')->where('platform_team_id', $team->id)->count()]);
    }

    public function update(Request $r, string $team_uuid)
    {
        $team = DB::table('platform_teams')->where('uuid', $team_uuid)->whereNull('deleted_at')->first(); abort_if(! $team, 404);
        $d = $this->data($r, $team->id); $d['updated_at'] = now();
        DB::table('platform_teams')->where('id', $team->id)->update($d);
        $fresh = DB::table('platform_teams')->where('id', $team->id)->first();
        $this->admin->audit($r, 'platform_team_updated', 'platform_team', $team->id, (array) $team, (array) $fresh);
        return $this->success(['team' => $fresh], 'Platform team updated.');
    }

    public function destroy(Request $r, string $team_uuid)
    {
        $team = DB::table('platform_teams')->where('uuid', $team_uuid)->whereNull('deleted_at')->first(); abort_if(! $team, 404);
        DB::table('platform_teams')->where('id', $team->id)->update(['deleted_at' => now(), 'status' => 'inactive', 'updated_at' => now()]);
        $this->admin->audit($r, 'platform_team_archived', 'platform_team', $team->id, (array) $team);
        return $this->success(null, 'Platform team archived.');
    }

    public function members(string $team_uuid)
    {
        $team = $this->team($team_uuid);
        return $this->success(['members' => DB::table('platform_team_members')->join('platform_users', 'platform_users.id', '=', 'platform_team_members.platform_user_id')->leftJoin('platform_team_roles', 'platform_team_roles.id', '=', 'platform_team_members.platform_team_role_id')->where('platform_team_id', $team->id)->get(['platform_team_members.*', 'platform_users.uuid as user_uuid', 'platform_users.display_name', 'platform_users.email', 'platform_team_roles.uuid as team_role_uuid', 'platform_team_roles.name as team_role_name'])]);
    }

    public function addMember(Request $r, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $d = $r->validate(['platform_user_uuid' => ['required', 'uuid'], 'team_role_uuid' => ['nullable', 'uuid'], 'joined_at' => ['nullable', 'date'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $userId = DB::table('platform_users')->where('uuid', $d['platform_user_uuid'])->value('id'); abort_if(! $userId, 404);
        $roleId = ! empty($d['team_role_uuid']) ? DB::table('platform_team_roles')->where('uuid', $d['team_role_uuid'])->value('id') : null;
        DB::table('platform_team_members')->updateOrInsert(['platform_team_id' => $team->id, 'platform_user_id' => $userId], ['platform_team_role_id' => $roleId, 'joined_at' => $d['joined_at'] ?? now()->toDateString(), 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->admin->audit($r, 'platform_team_member_added', 'platform_team', $team->id, null, ['platform_user_id' => $userId]);
        return $this->success(['member_added' => true], 'Team member saved.', 201);
    }

    public function updateMember(Request $r, string $team_uuid, int $member_id)
    {
        $team = $this->team($team_uuid);
        $m = DB::table('platform_team_members')->where('platform_team_id', $team->id)->where('id', $member_id)->first(); abort_if(! $m, 404);
        $d = $r->validate(['team_role_uuid' => ['nullable', 'uuid'], 'joined_at' => ['nullable', 'date'], 'left_at' => ['nullable', 'date'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        if (array_key_exists('team_role_uuid', $d)) $d['platform_team_role_id'] = $d['team_role_uuid'] ? DB::table('platform_team_roles')->where('uuid', $d['team_role_uuid'])->value('id') : null;
        unset($d['team_role_uuid']); $d['updated_at'] = now();
        DB::table('platform_team_members')->where('id', $member_id)->update($d);
        return $this->success(['member' => DB::table('platform_team_members')->where('id', $member_id)->first()], 'Team member updated.');
    }

    public function removeMember(Request $r, string $team_uuid, int $member_id)
    {
        $team = $this->team($team_uuid);
        DB::table('platform_team_members')->where('platform_team_id', $team->id)->where('id', $member_id)->delete();
        $this->admin->audit($r, 'platform_team_member_removed', 'platform_team', $team->id, ['member_id' => $member_id]);
        return $this->success(null, 'Team member removed.');
    }

    public function assignments(string $team_uuid) { $team = $this->team($team_uuid); return $this->success(['assignments' => DB::table('platform_team_assignments')->where('platform_team_id', $team->id)->latest('id')->get()]); }

    public function assign(Request $r, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $d = $r->validate(['assignable_type' => ['required', 'string', 'max:120'], 'assignable_id' => ['required', 'integer'], 'assignment_role' => ['nullable', 'string', 'max:80']]);
        $id = DB::table('platform_team_assignments')->insertGetId(['platform_team_id' => $team->id, ...$d, 'assigned_by' => $r->user()?->id, 'assigned_at' => now(), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $this->admin->audit($r, 'platform_team_assignment_created', 'platform_team', $team->id, null, ['assignment_id' => $id]);
        return $this->success(['assignment' => DB::table('platform_team_assignments')->where('id', $id)->first()], 'Assignment created.', 201);
    }

    public function releaseAssignment(Request $r, string $team_uuid, int $assignment_id)
    {
        $team = $this->team($team_uuid);
        DB::table('platform_team_assignments')->where('platform_team_id', $team->id)->where('id', $assignment_id)->update(['status' => 'released', 'released_at' => now(), 'updated_at' => now()]);
        return $this->success(null, 'Assignment released.');
    }

    public function teamRoles() { return $this->success(['team_roles' => DB::table('platform_team_roles')->orderBy('name')->get()]); }
    public function createTeamRole(Request $r) { $d = $r->validate(['name' => ['required', 'string'], 'code' => ['required', 'string', 'unique:platform_team_roles,code'], 'permissions' => ['nullable', 'array'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]); $id = DB::table('platform_team_roles')->insertGetId(['uuid' => (string) Str::uuid(), ...$d, 'permissions' => isset($d['permissions']) ? json_encode($d['permissions']) : null, 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]); return $this->success(['team_role' => DB::table('platform_team_roles')->where('id', $id)->first()], 'Team role created.', 201); }
    public function updateTeamRole(Request $r, string $role_uuid) { $role = DB::table('platform_team_roles')->where('uuid', $role_uuid)->first(); abort_if(! $role, 404); $d = $r->validate(['name' => ['sometimes', 'string'], 'permissions' => ['nullable', 'array'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]); if (isset($d['permissions'])) $d['permissions'] = json_encode($d['permissions']); $d['updated_at'] = now(); DB::table('platform_team_roles')->where('id', $role->id)->update($d); return $this->success(['team_role' => DB::table('platform_team_roles')->where('id', $role->id)->first()], 'Team role updated.'); }

    private function team(string $uuid): object { $team = DB::table('platform_teams')->where('uuid', $uuid)->whereNull('deleted_at')->first(); abort_if(! $team, 404); return $team; }
    private function data(Request $r, ?int $ignore = null): array { return $r->validate(['name' => [$ignore ? 'sometimes' : 'required', 'string', 'max:150'], 'code' => [$ignore ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('platform_teams')->ignore($ignore)], 'description' => ['nullable', 'string'], 'lead_platform_user_id' => ['nullable', 'integer', 'exists:platform_users,id'], 'email' => ['nullable', 'email'], 'color' => ['nullable', 'string', 'max:30'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]); }
}
