<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformTeamController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function index(Request $request)
    {
        $query = DB::table('platform_teams')->whereNull('deleted_at');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn($q) => $q
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%'));
        }

        foreach (['status', 'visibility'] as $field) {
            $value = $request->input('filter.' . $field, $request->input($field));
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $this->sort($query, $request, ['name', 'code', 'status', 'visibility', 'created_at', 'updated_at'], 'created_at');
        $page = $query->paginate((int) $request->integer('per_page', 25));
        return $this->list($page->items(), $page);
    }

    public function store(Request $request)
    {
        $data = $this->teamData($request);
        $id = DB::table('platform_teams')->insertGetId([
            'uuid' => (string) Str::uuid(),
            ...$data,
            'status' => $data['status'] ?? 'active',
            'visibility' => $data['visibility'] ?? 'internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $team = DB::table('platform_teams')->where('id', $id)->first();
        $this->admin->audit($request, 'platform_team_created', 'platform_team', $id, null, (array) $team);

        return $this->success(['team' => $team], 'Platform team created.', 201);
    }

    public function show(string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $team->members_count = DB::table('platform_team_members')->where('platform_team_id', $team->id)->count();
        $team->assignments_count = DB::table('platform_team_assignments')->where('platform_team_id', $team->id)->count();

        return $this->success(['team' => $team]);
    }

    public function update(Request $request, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $data = $this->teamData($request, $team->id, true);
        $data['updated_at'] = now();
        DB::table('platform_teams')->where('id', $team->id)->update($data);
        $fresh = DB::table('platform_teams')->where('id', $team->id)->first();
        $this->admin->audit($request, 'platform_team_updated', 'platform_team', $team->id, (array) $team, (array) $fresh);

        return $this->success(['team' => $fresh], 'Platform team updated.');
    }

    public function destroy(Request $request, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        DB::table('platform_teams')->where('id', $team->id)->update([
            'deleted_at' => now(),
            'status' => 'inactive',
            'updated_at' => now(),
        ]);
        $this->admin->audit($request, 'platform_team_archived', 'platform_team', $team->id, (array) $team, null, $request->input('audit_reason'));

        return $this->success(null, 'Platform team archived.');
    }

    public function members(string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $members = DB::table('platform_team_members')
            ->join('platform_users', 'platform_users.id', '=', 'platform_team_members.platform_user_id')
            ->leftJoin('platform_team_roles', 'platform_team_roles.id', '=', 'platform_team_members.platform_team_role_id')
            ->where('platform_team_id', $team->id)
            ->orderBy('platform_users.display_name')
            ->get([
                'platform_team_members.*',
                'platform_users.uuid as user_uuid',
                'platform_users.display_name',
                'platform_users.email',
                'platform_team_roles.uuid as team_role_uuid',
                'platform_team_roles.name as team_role_name',
            ]);

        return $this->success(['members' => $members]);
    }

    public function addMember(Request $request, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $data = $request->validate([
            'platform_user_uuid' => ['nullable', 'uuid'],
            'team_role_uuid' => ['nullable', 'uuid'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'members' => ['nullable', 'array'],
            'members.*.platform_user_uuid' => ['nullable', 'uuid'],
            'members.*.platform_user_id' => ['nullable'],
            'members.*.team_role_uuid' => ['nullable', 'uuid'],
            'members.*.platform_team_role_id' => ['nullable'],
            'members.*.joined_at' => ['nullable', 'date'],
            'members.*.effective_from' => ['nullable', 'date'],
            'members.*.status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $members = $data['members'] ?? [[
            'platform_user_uuid' => $data['platform_user_uuid'] ?? null,
            'team_role_uuid' => $data['team_role_uuid'] ?? null,
            'joined_at' => $data['joined_at'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]];

        $saved = [];
        foreach ($members as $member) {
            $userId = $this->platformUserId($member['platform_user_uuid'] ?? $member['platform_user_id'] ?? null);
            abort_if(! $userId, 404, 'Platform user not found.');
            $roleId = $this->teamRoleId($member['team_role_uuid'] ?? $member['platform_team_role_id'] ?? null);
            DB::table('platform_team_members')->updateOrInsert(
                ['platform_team_id' => $team->id, 'platform_user_id' => $userId],
                [
                    'platform_team_role_id' => $roleId,
                    'joined_at' => $member['joined_at'] ?? $member['effective_from'] ?? now()->toDateString(),
                    'status' => $member['status'] ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $saved[] = $userId;
        }

        $this->admin->audit($request, 'platform_team_member_added', 'platform_team', $team->id, null, ['platform_user_ids' => $saved]);

        return $this->success(['member_added' => true, 'platform_user_ids' => $saved], 'Team member saved.', 201);
    }

    public function updateMember(Request $request, string $team_uuid, int $member_id)
    {
        $team = $this->team($team_uuid);
        $member = DB::table('platform_team_members')->where('platform_team_id', $team->id)->where('id', $member_id)->first();
        abort_if(! $member, 404);
        $data = $request->validate([
            'team_role_uuid' => ['nullable', 'uuid'],
            'platform_team_role_id' => ['nullable'],
            'joined_at' => ['nullable', 'date'],
            'effective_from' => ['nullable', 'date'],
            'left_at' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        if (array_key_exists('team_role_uuid', $data) || array_key_exists('platform_team_role_id', $data)) {
            $data['platform_team_role_id'] = $this->teamRoleId($data['team_role_uuid'] ?? $data['platform_team_role_id'] ?? null);
        }
        if (isset($data['effective_from'])) {
            $data['joined_at'] = $data['effective_from'];
        }
        if (isset($data['effective_to'])) {
            $data['left_at'] = $data['effective_to'];
        }
        unset($data['team_role_uuid'], $data['effective_from'], $data['effective_to']);
        $data['updated_at'] = now();
        DB::table('platform_team_members')->where('id', $member_id)->update($data);

        return $this->success(['member' => DB::table('platform_team_members')->where('id', $member_id)->first()], 'Team member updated.');
    }

    public function removeMember(Request $request, string $team_uuid, int $member_id)
    {
        $team = $this->team($team_uuid);
        DB::table('platform_team_members')->where('platform_team_id', $team->id)->where('id', $member_id)->delete();
        $this->admin->audit($request, 'platform_team_member_removed', 'platform_team', $team->id, ['member_id' => $member_id], null, $request->input('audit_reason'));

        return $this->success(null, 'Team member removed.');
    }

    public function assignments(string $team_uuid)
    {
        $team = $this->team($team_uuid);

        return $this->success(['assignments' => DB::table('platform_team_assignments')->where('platform_team_id', $team->id)->latest('id')->get()]);
    }

    public function assign(Request $request, string $team_uuid)
    {
        $team = $this->team($team_uuid);
        $data = $request->validate([
            'assignable_type' => ['required', 'string', 'max:120'],
            'assignable_id' => ['required', 'integer'],
            'assignment_role' => ['nullable', 'string', 'max:80'],
        ]);
        $id = DB::table('platform_team_assignments')->insertGetId([
            'platform_team_id' => $team->id,
            ...$data,
            'assigned_by' => $request->user()?->id,
            'assigned_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->admin->audit($request, 'platform_team_assignment_created', 'platform_team', $team->id, null, ['assignment_id' => $id]);

        return $this->success(['assignment' => DB::table('platform_team_assignments')->where('id', $id)->first()], 'Assignment created.', 201);
    }

    public function releaseAssignment(Request $request, string $team_uuid, int $assignment_id)
    {
        $team = $this->team($team_uuid);
        DB::table('platform_team_assignments')->where('platform_team_id', $team->id)->where('id', $assignment_id)->update([
            'status' => 'released',
            'released_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(null, 'Assignment released.');
    }

    public function teamRoles(Request $request)
    {
        $query = DB::table('platform_team_roles')->whereNull('deleted_at');
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%'));
        }
        if ($request->filled('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }
        $this->sort($query, $request, ['name', 'code', 'status', 'sort_order', 'created_at', 'updated_at'], 'sort_order');
        $page = $query->paginate((int) $request->integer('per_page', 25));
        $items = collect($page->items())->map(fn($role) => $this->teamRolePayload($role))->all();

        return $this->list($items, $page);
    }

    public function showTeamRole(string $role_uuid)
    {
        return $this->success(['team_role' => $this->teamRolePayload($this->teamRole($role_uuid))]);
    }

    public function createTeamRole(Request $request)
    {
        $data = $this->teamRoleData($request);
        $id = DB::table('platform_team_roles')->insertGetId([
            'uuid' => (string) Str::uuid(),
            ...$data,
            'permissions' => isset($data['permissions']) ? json_encode($data['permissions']) : null,
            'status' => $data['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['team_role' => $this->teamRolePayload(DB::table('platform_team_roles')->where('id', $id)->first())], 'Team role created.', 201);
    }

    public function updateTeamRole(Request $request, string $role_uuid)
    {
        $role = $this->teamRole($role_uuid);
        $data = $this->teamRoleData($request, $role->id, true);
        if (isset($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }
        $data['updated_at'] = now();
        DB::table('platform_team_roles')->where('id', $role->id)->update($data);

        return $this->success(['team_role' => $this->teamRolePayload(DB::table('platform_team_roles')->where('id', $role->id)->first())], 'Team role updated.');
    }

    public function deleteTeamRole(Request $request, string $role_uuid)
    {
        $role = $this->teamRole($role_uuid);
        if ((bool) ($role->is_system ?? false)) {
            return $this->businessError('System team roles cannot be deleted.', 'SYSTEM_TEAM_ROLE_DELETE_FORBIDDEN', 403);
        }
        if (DB::table('platform_team_members')->where('platform_team_role_id', $role->id)->exists()) {
            return $this->businessError('Assigned team roles cannot be deleted.', 'TEAM_ROLE_IN_USE', 409);
        }
        DB::table('platform_team_roles')->where('id', $role->id)->update(['deleted_at' => now(), 'status' => 'inactive', 'updated_at' => now()]);
        $this->admin->audit($request, 'platform_team_role_deleted', 'platform_team_role', $role->id, (array) $role, null, $request->input('audit_reason'));

        return $this->success(null, 'Team role deleted.');
    }

    private function team(string $uuid): object
    {
        $team = DB::table('platform_teams')->where('uuid', $uuid)->whereNull('deleted_at')->first();
        abort_if(! $team, 404);

        return $team;
    }

    private function teamRole(string $uuid): object
    {
        $role = DB::table('platform_team_roles')->where('uuid', $uuid)->whereNull('deleted_at')->first();
        abort_if(! $role, 404);

        return $role;
    }

    private function platformUserId(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;

        return DB::table('platform_users')->where('uuid', $value)->value('id');
    }

    private function teamRoleId(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int) $value;

        return DB::table('platform_team_roles')->where('uuid', $value)->value('id');
    }

    private function teamData(Request $request, ?int $ignore = null, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:150'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('platform_teams')->ignore($ignore)],
            'description' => ['nullable', 'string', 'max:255'],
            'lead_platform_user_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'assistant_lead_platform_user_id' => ['nullable', 'integer', 'exists:platform_users,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:80'],
            'visibility' => ['nullable', Rule::in(['internal', 'private'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function teamRoleData(Request $request, ?int $ignore = null, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:150'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('platform_team_roles', 'code')->ignore($ignore, 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
            'is_system' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function teamRolePayload(object $role): object
    {
        $permissions = $role->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $role->permissions = $permissions;
        $role->permissions_count = collect($permissions)->flatten()->count();

        return $role;
    }
    private function sort(Builder $query, Request $request, array $allowed, string $default): void
    {
        $sort = (string) $request->input('sort', $default);
        if (! in_array($sort, $allowed, true)) {
            $sort = $default;
        }
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }
}
