<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantTeamController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function index(Request $request): JsonResponse
    {
        $query = DB::table('teams')->where('teams.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereNull('teams.deleted_at')
            ->leftJoin('departments', 'departments.id', '=', 'teams.department_id')
            ->leftJoin('tenant_offices', 'tenant_offices.id', '=', 'teams.office_id')
            ->leftJoin('users as leads', 'leads.id', '=', 'teams.lead_user_id')
            ->select('teams.*', 'departments.uuid as department_uuid', 'departments.name as department_name', 'tenant_offices.uuid as office_uuid', 'tenant_offices.name as office_name', 'leads.uuid as lead_user_uuid', 'leads.display_name as lead_name')
            ->selectSub(fn ($q) => $q->from('team_members')->selectRaw('count(*)')->whereColumn('team_members.team_id', 'teams.id')->where('team_members.tenant_id', app(\App\Tenancy\TenantContext::class)->id()), 'members_count');

        foreach (['status', 'visibility'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where('teams.'.$field, $request->input('filter.'.$field));
            }
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q->where('teams.name', 'like', '%'.$request->search.'%')->orWhere('teams.code', 'like', '%'.$request->search.'%'));
        }

        $page = $query->orderBy('teams.name')->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->teamData($request);
        $id = DB::table('teams')->insertGetId([...$data, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $team = DB::table('teams')->where('id', $id)->first();
        $this->tenant->audit($request, 'tenant_team_created', 'team', $id, null, (array) $team);

        return $this->success(['team' => $team], 'Team created.', 201);
    }

    public function show(string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);

        return $this->success(['team' => (array) $team + [
            'members_count' => $this->memberQuery($team->id)->count(),
            'permissions_count' => DB::table('team_permissions')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->count(),
            'assignments_count' => DB::table('team_assignments')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->where('status', 'active')->count(),
        ]]);
    }

    public function update(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $old = (array) $team;
        $data = $this->teamData($request, true);
        DB::table('teams')->where('id', $team->id)->update([...$data, 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $new = (array) DB::table('teams')->where('id', $team->id)->first();
        $this->tenant->audit($request, 'tenant_team_updated', 'team', $team->id, $old, $new);

        return $this->success(['team' => $new], 'Team updated.');
    }

    public function destroy(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        DB::table('teams')->where('id', $team->id)->update(['deleted_at' => now(), 'updated_by' => $request->user()?->id, 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_team_deleted', 'team', $team->id, (array) $team, null);

        return $this->success(null, 'Team archived.');
    }

    public function members(string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);

        return $this->success(['members' => $this->memberQuery($team->id)->get()]);
    }

    public function addMembers(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $data = $request->validate(['members' => ['required', 'array', 'min:1'], 'members.*.user_id' => ['nullable', 'string'], 'members.*.staff_id' => ['nullable', 'string'], 'members.*.team_role_id' => ['nullable', 'string'], 'members.*.member_type' => ['nullable', 'string', 'max:50'], 'members.*.allocation_percent' => ['nullable', 'numeric', 'min:0', 'max:100'], 'members.*.is_primary' => ['nullable', 'boolean'], 'members.*.effective_from' => ['nullable', 'date'], 'members.*.effective_to' => ['nullable', 'date'], 'members.*.status' => ['nullable', 'string', 'max:50']]);
        $ids = [];
        foreach ($data['members'] as $member) {
            $ids[] = DB::table('team_members')->insertGetId($this->memberData($request, $team->id, $member));
        }
        $this->tenant->audit($request, 'tenant_team_members_added', 'team', $team->id, null, ['member_ids' => $ids]);

        return $this->success(['members' => $this->memberQuery($team->id)->whereIn('team_members.id', $ids)->get()], 'Members added.', 201);
    }

    public function updateMember(Request $request, string $team_uuid, string $member_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $member = DB::table('team_members')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->where('uuid', $member_uuid)->first() ?: abort(404, 'Member not found.');
        $old = (array) $member;
        DB::table('team_members')->where('id', $member->id)->update([...$this->memberData($request, $team->id, $request->all(), true), 'updated_at' => now()]);
        $new = (array) DB::table('team_members')->where('id', $member->id)->first();
        $this->tenant->audit($request, 'tenant_team_member_updated', 'team_member', $member->id, $old, $new);

        return $this->success(['member' => $new], 'Member updated.');
    }

    public function removeMember(Request $request, string $team_uuid, string $member_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $member = DB::table('team_members')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->where('uuid', $member_uuid)->first() ?: abort(404, 'Member not found.');
        DB::table('team_members')->where('id', $member->id)->delete();
        $this->tenant->audit($request, 'tenant_team_member_removed', 'team_member', $member->id, (array) $member, null);

        return $this->success(null, 'Member removed.');
    }

    public function permissions(string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $items = DB::table('team_permissions')->join('permissions', 'permissions.id', '=', 'team_permissions.permission_id')->where('team_permissions.tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_permissions.team_id', $team->id)->orderBy('permissions.module')->orderBy('permissions.name')->get(['permissions.uuid', 'permissions.module', 'permissions.name', 'permissions.display_name', 'permissions.description']);

        return $this->success(['permissions' => $items->groupBy('module')->map(fn ($rows) => $rows->values()->all())->all()]);
    }

    public function syncPermissions(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $data = $request->validate(['permission_ids' => ['required', 'array'], 'permission_ids.*' => ['required', 'string']]);
        $permissionIds = DB::table('permissions')->whereIn('uuid', $data['permission_ids'])->where('guard_name', 'tenant')->pluck('id')->all();
        DB::table('team_permissions')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->delete();
        foreach ($permissionIds as $permissionId) {
            DB::table('team_permissions')->insert(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'team_id' => $team->id, 'permission_id' => $permissionId, 'granted_by' => $request->user()?->id, 'granted_at' => now()]);
        }
        $this->tenant->audit($request, 'tenant_team_permissions_synced', 'team', $team->id, null, ['permission_ids' => $permissionIds]);

        return $this->success(['permission_count' => count($permissionIds)], 'Team permissions updated.');
    }

    public function settings(string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);

        return $this->success(['settings' => DB::table('team_settings')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->get()->map(fn ($row) => [...(array) $row, 'value' => json_decode((string) $row->value, true)])->all()]);
    }

    public function updateSettings(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $data = $request->validate(['settings' => ['required', 'array'], 'settings.*.group' => ['required', 'string', 'max:100'], 'settings.*.key' => ['required', 'string', 'max:150'], 'settings.*.value' => ['nullable'], 'settings.*.value_type' => ['nullable', 'string', 'max:50']]);
        foreach ($data['settings'] as $setting) {
            DB::table('team_settings')->updateOrInsert(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'team_id' => $team->id, 'group' => $setting['group'], 'key' => $setting['key']], ['value' => json_encode($setting['value'] ?? null), 'value_type' => $setting['value_type'] ?? 'json', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->tenant->audit($request, 'tenant_team_settings_updated', 'team', $team->id, null, $data);

        return $this->settings($team_uuid);
    }

    public function assignments(string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);

        return $this->success(['assignments' => DB::table('team_assignments')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->orderByDesc('id')->get()]);
    }

    public function createAssignment(Request $request, string $team_uuid): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $data = $request->validate(['assignable_type' => ['required', Rule::in(['lead', 'client', 'vendor', 'project', 'task', 'client_issue', 'calendar_event'])], 'assignable_id' => ['required', 'string'], 'assignment_role' => ['nullable', 'string', 'max:80'], 'assigned_at' => ['nullable', 'date'], 'status' => ['nullable', 'string', 'max:50']]);
        $table = ['lead' => 'lead_profiles', 'client' => 'client_profiles', 'vendor' => 'vendor_profiles', 'project' => 'projects', 'task' => 'tasks', 'client_issue' => 'client_issues', 'calendar_event' => 'calendar_events'][$data['assignable_type']];
        $assignableId = $this->tenant->uuidToId($table, $data['assignable_id'], false);
        $id = DB::table('team_assignments')->insertGetId(['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'team_id' => $team->id, 'assignable_type' => $data['assignable_type'], 'assignable_id' => $assignableId, 'assignment_role' => $data['assignment_role'] ?? null, 'assigned_by' => $request->user()?->id, 'assigned_at' => $data['assigned_at'] ?? now(), 'status' => $data['status'] ?? 'active']);
        $this->tenant->audit($request, 'tenant_team_assignment_created', 'team_assignment', $id, null, $data);

        return $this->success(['assignment' => DB::table('team_assignments')->where('id', $id)->first()], 'Assignment created.', 201);
    }

    public function releaseAssignment(Request $request, string $team_uuid, int $assignment_id): JsonResponse
    {
        $team = $this->tenant->byUuid('teams', $team_uuid, false);
        $assignment = DB::table('team_assignments')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_id', $team->id)->where('id', $assignment_id)->first() ?: abort(404, 'Assignment not found.');
        DB::table('team_assignments')->where('id', $assignment->id)->update(['status' => 'released', 'released_at' => now()]);
        $this->tenant->audit($request, 'tenant_team_assignment_released', 'team_assignment', $assignment->id, (array) $assignment, null);

        return $this->success(null, 'Assignment released.');
    }

    public function teamRoles(Request $request): JsonResponse
    {
        $page = DB::table('team_roles')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->orderBy('sort_order')->orderBy('name')->paginate((int) $request->integer('per_page', 50));

        return $this->list($page->items(), $page);
    }

    public function storeTeamRole(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'string', 'max:80'], 'description' => ['nullable', 'string'], 'permissions' => ['nullable', 'array'], 'sort_order' => ['nullable', 'integer'], 'status' => ['nullable', 'string', 'max:50']]);
        $id = DB::table('team_roles')->insertGetId([...$data, 'permissions' => json_encode($data['permissions'] ?? []), 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'created_at' => now(), 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_team_role_created', 'team_role', $id, null, $data);

        return $this->success(['team_role' => DB::table('team_roles')->where('id', $id)->first()], 'Team role created.', 201);
    }

    public function updateTeamRole(Request $request, string $team_role_uuid): JsonResponse
    {
        $role = $this->tenant->byUuid('team_roles', $team_role_uuid, false);
        if ($role->is_system && ($request->filled('name') || $request->filled('code'))) {
            return $this->businessError('System team roles cannot be renamed.', 'SYSTEM_TEAM_ROLE_RENAME_FORBIDDEN', 403);
        }
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:150'], 'code' => ['sometimes', 'string', 'max:80'], 'description' => ['nullable', 'string'], 'permissions' => ['nullable', 'array'], 'sort_order' => ['nullable', 'integer'], 'status' => ['nullable', 'string', 'max:50']]);
        if (array_key_exists('permissions', $data)) {
            $data['permissions'] = json_encode($data['permissions']);
        }
        DB::table('team_roles')->where('id', $role->id)->update([...$data, 'updated_at' => now()]);
        $this->tenant->audit($request, 'tenant_team_role_updated', 'team_role', $role->id, (array) $role, $data);

        return $this->success(['team_role' => DB::table('team_roles')->where('id', $role->id)->first()], 'Team role updated.');
    }

    public function deleteTeamRole(Request $request, string $team_role_uuid): JsonResponse
    {
        $role = $this->tenant->byUuid('team_roles', $team_role_uuid, false);
        if ($role->is_system) {
            return $this->businessError('System team roles cannot be deleted.', 'SYSTEM_TEAM_ROLE_DELETE_FORBIDDEN', 403);
        }
        if (DB::table('team_members')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->where('team_role_id', $role->id)->exists()) {
            return $this->businessError('Assigned team roles cannot be deleted.', 'TEAM_ROLE_IN_USE', 409);
        }
        DB::table('team_roles')->where('id', $role->id)->delete();
        $this->tenant->audit($request, 'tenant_team_role_deleted', 'team_role', $role->id, (array) $role, null);

        return $this->success(null, 'Team role deleted.');
    }

    public function export(Request $request): JsonResponse
    {
        return $this->success(['job' => $this->tenant->createJob($request, 'export', 'teams', $request->all())], 'Teams export queued.', 202);
    }

    private function teamData(Request $request, bool $partial = false): array
    {
        $data = $request->validate(['parent_team_id' => ['nullable', 'string'], 'department_id' => ['nullable', 'string'], 'office_id' => ['nullable', 'string'], 'team_type_id' => ['nullable', 'string'], 'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:150'], 'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80'], 'description' => ['nullable', 'string'], 'lead_user_id' => ['nullable', 'string'], 'assistant_lead_user_id' => ['nullable', 'string'], 'email' => ['nullable', 'email', 'max:150'], 'phone' => ['nullable', 'string', 'max:20'], 'color' => ['nullable', 'string', 'max:30'], 'icon' => ['nullable', 'string', 'max:80'], 'visibility' => ['nullable', 'string', 'max:50'], 'is_default' => ['nullable', 'boolean'], 'status' => ['nullable', 'string', 'max:50']]);
        foreach (['parent_team_id' => 'teams', 'department_id' => 'departments', 'office_id' => 'tenant_offices', 'team_type_id' => 'tenant_lookups', 'lead_user_id' => 'users', 'assistant_lead_user_id' => 'users'] as $key => $table) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->tenant->uuidToId($table, $data[$key], true);
            }
        }

        return $data;
    }

    private function memberData(Request $request, int $teamId, array $member, bool $partial = false): array
    {
        $data = [];
        foreach (['user_id' => 'users', 'staff_id' => 'staff', 'team_role_id' => 'team_roles'] as $key => $table) {
            if (array_key_exists($key, $member)) {
                $data[$key] = $this->tenant->uuidToId($table, $member[$key] ?? null, true);
            }
        }
        foreach (['member_type', 'allocation_percent', 'is_primary', 'effective_from', 'effective_to', 'status'] as $key) {
            if (array_key_exists($key, $member)) {
                $data[$key] = $member[$key];
            }
        }

        return $partial ? $data : [...$data, 'uuid' => (string) \Illuminate\Support\Str::uuid(), 'tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'team_id' => $teamId, 'created_by' => $request->user()?->id, 'updated_by' => $request->user()?->id, 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()];
    }

    private function memberQuery(int $teamId)
    {
        return DB::table('team_members')
            ->leftJoin('users', 'users.id', '=', 'team_members.user_id')
            ->leftJoin('staff', 'staff.id', '=', 'team_members.staff_id')
            ->leftJoin('team_roles', 'team_roles.id', '=', 'team_members.team_role_id')
            ->where('team_members.tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->where('team_members.team_id', $teamId)
            ->select('team_members.*', 'users.uuid as user_uuid', 'users.display_name as user_name', 'staff.uuid as staff_uuid', 'staff.display_name as staff_name', 'team_roles.uuid as team_role_uuid', 'team_roles.name as team_role_name');
    }
}
