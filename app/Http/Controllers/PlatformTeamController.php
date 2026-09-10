<?php
namespace App\Http\Controllers;

use App\Http\Requests\ListPlatformTeamsRequest;
use App\Http\Requests\StorePlatformTeamRequest;
use App\Http\Requests\StoreTeamAssignmentRequest;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdatePlatformTeamRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Models\PlatformDepartment;
use App\Models\PlatformTeam;
use App\Models\PlatformTeamAssignment;
use App\Models\PlatformTeamMember;
use App\Models\PlatformTeamRole;
use App\Models\PlatformUser;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PlatformTeamController extends Controller
{
    public function index(ListPlatformTeamsRequest $request): mixed
    {
        $input = $request->validated();
        $query = PlatformTeam::with(['department', 'lead', 'assistantLead', 'users.department', 'users.designation'])->withCount(['users as members_count', 'assignments'])->orderBy($input['sort'] ?? 'name', $input['direction'] ?? 'asc');
        $search = $input['search'] ?? null;
        if ($search) $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
        $status = $input['filter']['status'] ?? $input['status'] ?? null; $visibility = $input['filter']['visibility'] ?? $input['visibility'] ?? null;
        if ($status) $query->where('status', $status); if ($visibility) $query->where('visibility', $visibility);
        $paginator = $query->paginate($request->integer('per_page', 25))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Platform teams fetched.', 200, ['current_page'=>$paginator->currentPage(),'per_page'=>$paginator->perPage(),'total'=>$paginator->total(),'last_page'=>$paginator->lastPage()]);
    }

    public function store(StorePlatformTeamRequest $request): mixed
    {
        $input = $request->validated();
        $data = collect($input)->except(['code', 'department_uuid', 'lead_platform_user_uuid', 'assistant_lead_platform_user_uuid'])->all();
        if (array_key_exists('department_uuid', $input)) $data['platform_department_id'] = $this->resolveId($input, 'department_uuid', null, PlatformDepartment::class);
        if (array_key_exists('lead_platform_user_uuid', $input)) $data['lead_platform_user_id'] = $this->resolveId($input, 'lead_platform_user_uuid', null, PlatformUser::class);
        if (array_key_exists('assistant_lead_platform_user_uuid', $input)) $data['assistant_lead_platform_user_id'] = $this->resolveId($input, 'assistant_lead_platform_user_uuid', null, PlatformUser::class);
        if (($data['lead_platform_user_id'] ?? null) && ($data['lead_platform_user_id'] ?? null) === ($data['assistant_lead_platform_user_id'] ?? null)) return ApiResponse::error('Lead and assistant lead must be different users.', 422, null, 'TEAM_LEADS_MUST_DIFFER');
        $team = PlatformTeam::create(array_merge(['visibility'=>'internal','status'=>'active'], $data));
        ActivityLogger::record($request, 'platform_team.created', $team, 'Platform team created.');
        return ApiResponse::success(['team'=>$this->loadTeam($team)], 'Platform team created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        return ApiResponse::success(['team'=>$this->loadTeam($team)], 'Platform team fetched.');
    }

    public function update(UpdatePlatformTeamRequest $request, string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        $input = $request->validated();
        $data = collect($input)->except(['code', 'department_uuid', 'lead_platform_user_uuid', 'assistant_lead_platform_user_uuid'])->all();
        if (array_key_exists('department_uuid', $input)) $data['platform_department_id'] = $this->resolveId($input, 'department_uuid', null, PlatformDepartment::class);
        if (array_key_exists('lead_platform_user_uuid', $input)) $data['lead_platform_user_id'] = $this->resolveId($input, 'lead_platform_user_uuid', null, PlatformUser::class);
        if (array_key_exists('assistant_lead_platform_user_uuid', $input)) $data['assistant_lead_platform_user_id'] = $this->resolveId($input, 'assistant_lead_platform_user_uuid', null, PlatformUser::class);
        $lead = array_key_exists('lead_platform_user_id', $data) ? $data['lead_platform_user_id'] : $team->lead_platform_user_id;
        $assistant = array_key_exists('assistant_lead_platform_user_id', $data) ? $data['assistant_lead_platform_user_id'] : $team->assistant_lead_platform_user_id;
        if ($lead && $lead === $assistant) return ApiResponse::error('Lead and assistant lead must be different users.', 422, null, 'TEAM_LEADS_MUST_DIFFER');
        $team->update($data); return ApiResponse::success(['team'=>$this->loadTeam($team)], 'Platform team updated successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        $team->update(['status'=>'inactive']); $team->delete(); ActivityLogger::record($request,'platform_team.deleted',$team,'Platform team archived.');
        return ApiResponse::success(null,'Platform team archived successfully.');
    }

    public function members(string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        $members = $this->presentMembers(PlatformTeamMember::with(['team', 'user.department', 'user.designation', 'user.manager', 'teamRole'])->where('platform_team_id', $team->id)->get()->sortBy(fn (PlatformTeamMember $member) => $member->user?->display_name)->values());
        return ApiResponse::success(['members' => $members], 'Platform team members fetched.');
    }

    public function addMembers(StoreTeamMemberRequest $request, string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        $input = $request->validated(); $items = $input['members'] ?? [$input];
        $affected = [];
        foreach ($items as $item) { $userId = $this->resolveId($item,'platform_user_uuid','platform_user_id',PlatformUser::class); $roleId = $this->resolveId($item,'team_role_uuid','platform_team_role_id',PlatformTeamRole::class); if (! $userId) continue; PlatformTeamMember::updateOrCreate(['platform_team_id'=>$team->id,'platform_user_id'=>$userId],['platform_team_role_id'=>$roleId,'joined_at'=>$item['joined_at'] ?? $item['effective_from'] ?? now()->toDateString(),'status'=>$item['status'] ?? 'active']); $affected[]=$userId; }
        return ApiResponse::success(['member_added'=>count($affected), 'user_ids'=>$affected, 'members'=>$this->presentMembers(PlatformTeamMember::with(['team', 'user.department', 'user.designation', 'user.manager', 'teamRole'])->where('platform_team_id', $team->id)->get())], 'Platform team members saved.');
    }

    public function updateMember(UpdateTeamMemberRequest $request, string $uuid, int $memberId): mixed
    {
        $team = $this->findTeam($uuid); $member = $team ? PlatformTeamMember::where('platform_team_id',$team->id)->whereKey($memberId)->first() : null;
        if (! $team || ! $member) return ApiResponse::error('Team member not found.',404,null,'TEAM_MEMBER_NOT_FOUND');
        $input = $request->validated(); $roleId = array_key_exists('team_role_uuid',$input) ? $this->resolveId($input,'team_role_uuid',null,PlatformTeamRole::class) : ($input['platform_team_role_id'] ?? $member->platform_team_role_id);
        $member->update(['platform_team_role_id'=>$roleId,'joined_at'=>$input['joined_at'] ?? $input['effective_from'] ?? $member->joined_at,'left_at'=>$input['left_at'] ?? $input['effective_to'] ?? $member->left_at,'status'=>$input['status'] ?? $member->status]);
        return ApiResponse::success(['member'=>$member->fresh()->load(['team', 'user.department', 'user.designation', 'user.manager', 'teamRole'])], 'Team member updated successfully.');
    }

    public function removeMember(string $uuid, int $memberId): mixed
    {
        $team = $this->findTeam($uuid); $member = $team ? PlatformTeamMember::where('platform_team_id',$team->id)->whereKey($memberId)->first() : null;
        if (! $team || ! $member) return ApiResponse::error('Team member not found.',404,null,'TEAM_MEMBER_NOT_FOUND');
        $member->delete(); return ApiResponse::success(null,'Team member removed successfully.');
    }

    public function assignments(string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        return ApiResponse::success([
            'assignments' => $team->assignments()->with(['team', 'assignedBy', 'assignable'])->latest()->get(),
        ], 'Platform team assignments fetched.');
    }

    public function addAssignment(StoreTeamAssignmentRequest $request, string $uuid): mixed
    {
        $team = $this->findTeam($uuid); if (! $team) return ApiResponse::error('Platform team not found.',404,null,'PLATFORM_TEAM_NOT_FOUND');
        $assignment = new PlatformTeamAssignment(); $assignment->forceFill(array_merge($request->validated(),['platform_team_id'=>$team->id,'assigned_by'=>$request->user()->id,'assigned_at'=>now(),'status'=>'active']))->save();
        return ApiResponse::success(['assignment'=>$assignment->fresh()->load(['team', 'assignedBy', 'assignable'])], 'Platform team assignment created successfully.',201);
    }

    public function releaseAssignment(string $uuid, int $assignmentId): mixed
    {
        $team = $this->findTeam($uuid); $assignment = $team ? $team->assignments()->whereKey($assignmentId)->first() : null;
        if (! $team || ! $assignment) return ApiResponse::error('Team assignment not found.',404,null,'TEAM_ASSIGNMENT_NOT_FOUND');
        $assignment->update(['status'=>'released','released_at'=>now()]); return ApiResponse::success(null,'Platform team assignment released successfully.');
    }

    private function presentMembers($members)
    {
        return $members->map(function (PlatformTeamMember $member): array {
            $data = $member->toArray();
            $user = $member->user;
            $role = $member->teamRole;
            $data['display_name'] = $user?->display_name;
            $data['email'] = $user?->email;
            $data['platform_user_name'] = $user?->display_name;
            $data['team_role_name'] = $role?->name;
            $data['team_role_uuid'] = $role?->uuid;
            return $data;
        })->values();
    }
    private function findTeam(string $uuid): ?PlatformTeam { return PlatformTeam::where('uuid',$uuid)->first(); }
    private function loadTeam(PlatformTeam $team): PlatformTeam { return $team->fresh()->load(['department', 'lead', 'assistantLead', 'users.department', 'users.designation'])->loadCount(['users as members_count', 'assignments']); }
    private function resolveId(array $input, ?string $uuidKey, ?string $idKey, string $model): ?int { if ($uuidKey && array_key_exists($uuidKey,$input)) return $model::where('uuid',$input[$uuidKey])->value('id'); return $idKey ? ($input[$idKey] ?? null) : null; }
}
