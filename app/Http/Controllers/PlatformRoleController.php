<?php
namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleUsersRequest;
use App\Http\Requests\ClonePlatformRoleRequest;
use App\Http\Requests\ExportPlatformRolesRequest;
use App\Http\Requests\ListPlatformRolesRequest;
use App\Http\Requests\ReplaceRolePermissionsRequest;
use App\Http\Requests\StorePlatformRoleRequest;
use App\Http\Requests\UpdatePlatformRoleRequest;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PlatformRoleController extends Controller
{
    private const COLUMNS = ['uuid','name','display_name','guard_name','is_system','status','permissions_count','users_count','created_at','updated_at'];

    public function index(ListPlatformRolesRequest $request): mixed
    {
        $baseQuery = $this->filteredQuery($request->validated());
        $kpiRows = (clone $baseQuery)->withCount(['permissions', 'platformUsers', 'platformUsers as users_count'])->get();
        $paginator = (clone $baseQuery)
            ->with(['permissions', 'platformUsers.department', 'platformUsers.designation', 'platformUsers.teams'])
            ->withCount(['permissions', 'platformUsers', 'platformUsers as users_count'])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $kpis = [
            'total' => $kpiRows->count(),
            'active' => $kpiRows->where('status', 'active')->count(),
            'inactive' => $kpiRows->where('status', 'inactive')->count(),
            'system' => $kpiRows->where('is_system', true)->count(),
            'custom' => $kpiRows->where('is_system', false)->count(),
            'assignments' => $kpiRows->sum('platform_users_count'),
            'permissions' => $kpiRows->sum('permissions_count'),
        ];

        $paginator->setCollection($paginator->getCollection()->map(fn (PlatformRole $role): PlatformRole => $this->presentRole($role)));

        return ApiResponse::success($paginator->items(), 'Platform roles fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
            'kpis' => $kpis,
        ]);
    }

    public function store(StorePlatformRoleRequest $request): mixed
    {
        $input = $request->validated();
        $role = new PlatformRole();
        $role->forceFill(array_merge(['uuid' => (string) Str::uuid(), 'guard_name' => 'platform', 'status' => 'active', 'is_system' => false], collect($input)->except('permission_ids', 'audit_reason')->all()))->save();
        $this->syncPermissions($role, $input['permission_ids'] ?? []);
        return ApiResponse::success(['role' => $this->presentRole($role->fresh())], 'Platform role created successfully.', 201);
    }

    public function show(string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        return ApiResponse::success(['role' => $this->presentRole($role)], 'Platform role fetched.');
    }

    public function update(UpdatePlatformRoleRequest $request, string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        $input = $request->validated();
        if ($role->is_system && isset($input['name']) && $input['name'] !== $role->name) return ApiResponse::error('System roles cannot be renamed.', 403, null, 'SYSTEM_ROLE_RENAME_FORBIDDEN');
        unset($input['permission_ids'], $input['audit_reason'], $input['is_system']);
        $role->forceFill($input)->save();
        if ($request->has('permission_ids')) $this->syncPermissions($role, $request->validated()['permission_ids']);
        return ApiResponse::success(['role' => $this->presentRole($role->fresh())], 'Platform role updated successfully.');
    }

    public function destroy(string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        if ($role->is_system) return ApiResponse::error('System roles cannot be deleted.', 403, null, 'SYSTEM_ROLE_DELETE_FORBIDDEN');
        if ($role->platformUsers()->exists()) return ApiResponse::error('Role is assigned to users.', 409, null, 'ROLE_IN_USE');
        $role->permissions()->detach();
        $role->delete();
        return ApiResponse::success(null, 'Platform role deleted successfully.');
    }

    public function clone(ClonePlatformRoleRequest $request, string $roleUuid): mixed
    {
        $source = $this->findRole($roleUuid);
        if (! $source) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        $input = $request->validated();
        if (PlatformRole::where('name', $input['name'])->where('guard_name', $source->guard_name)->exists()) return ApiResponse::validation(['name' => ['The name has already been taken for this guard.']]);
        $role = new PlatformRole();
        $role->forceFill(['uuid' => (string) Str::uuid(), 'name' => $input['name'], 'display_name' => $input['display_name'], 'guard_name' => $source->guard_name, 'description' => ($input['copy_description'] ?? true) ? $source->description : null, 'is_system' => false, 'status' => $input['status'] ?? 'inactive'])->save();
        if ($input['copy_permissions'] ?? true) $role->permissions()->sync($source->permissions()->pluck('platform_permissions.id'));
        return ApiResponse::success(['role' => $this->presentRole($role->fresh())], 'Platform role cloned successfully.', 201);
    }

    public function activate(string $roleUuid): mixed { return $this->setStatus($roleUuid, 'active'); }
    public function deactivate(string $roleUuid): mixed { return $this->setStatus($roleUuid, 'inactive'); }

    public function permissions(string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        return ApiResponse::success($role->permissions()->with(['roles'])->where('status', 'active')->orderBy('module')->orderBy('name')->get()->groupBy('module'), 'Role permissions fetched.');
    }

    public function replacePermissions(ReplaceRolePermissionsRequest $request, string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        $this->syncPermissions($role, $request->validated()['permission_ids']);
        return ApiResponse::success(['role' => $this->presentRole($role->fresh())], 'Role permissions replaced successfully.');
    }

    public function users(string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');
        $users = $role->platformUsers()->with(['department:id,uuid,name', 'designation:id,uuid,name', 'teams'])->orderBy('display_name')->get(['id','uuid','display_name','email','department_id','designation_id','status']);
        return ApiResponse::success($users, 'Role users fetched.');
    }

    public function assignUsers(AssignRoleUsersRequest $request, string $roleUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND');

        $input = $request->validated();
        $users = PlatformUser::whereIn('uuid', $input['platform_user_ids'])->get();
        $effectiveDate = $input['effective_date'] ?? null;
        $sync = [];
        foreach ($users as $user) {
            $sync[$user->id] = ['effective_date' => $effectiveDate];
        }
        $role->platformUsers()->syncWithoutDetaching($sync);

        $notificationResults = ['requested' => (bool) ($input['notify_users'] ?? false), 'sent' => 0, 'failed' => 0];
        if ($notificationResults['requested']) {
            foreach ($users as $user) {
                try {
                    $this->sendRoleAssignmentNotification($user, $role, $effectiveDate);
                    $notificationResults['sent']++;
                } catch (\Throwable $exception) {
                    report($exception);
                    $notificationResults['failed']++;
                }
            }
        }

        ActivityLogger::record($request, 'platform_role.users_assigned', $role, 'Users assigned to platform role.', [
            'platform_user_ids' => $users->pluck('id')->values()->all(),
            'effective_date' => $effectiveDate,
            'notify_users' => $notificationResults,
            'audit_reason' => $input['audit_reason'] ?? null,
        ]);

        return ApiResponse::success([
            'users' => $role->platformUsers()->with(['department', 'designation', 'teams'])->orderBy('display_name')->get(),
            'notifications' => $notificationResults,
        ], 'Users assigned to role successfully.');
    }

    public function removeUser(Request $request, string $roleUuid, string $platformUserUuid): mixed
    {
        $role = $this->findRole($roleUuid);
        $user = PlatformUser::where('uuid', $platformUserUuid)->first();
        if (! $role || ! $user) return ApiResponse::error('Role or platform user not found.', 404, null, 'ROLE_OR_USER_NOT_FOUND');
        $role->platformUsers()->detach($user->id);
        ActivityLogger::record($request, 'platform_role.user_removed', $role, 'User removed from platform role.', [
            'platform_user_id' => $user->id,
            'audit_reason' => $request->input('audit_reason'),
        ]);
        return ApiResponse::success(null, 'User removed from role successfully.');
    }

    private function sendRoleAssignmentNotification(PlatformUser $user, PlatformRole $role, ?string $effectiveDate): void
    {
        $template = DB::table('notification_templates')->whereNull('tenant_id')->where('channel', 'email')->where('code', 'platform_role.assigned')->where('status', 'active')->latest('id')->first();
        $displayName = (string) ($user->display_name ?: $user->email);
        $roleName = (string) ($role->display_name ?: $role->name);
        $subject = $template?->subject ?: 'You have been assigned a platform role';
        $body = $template?->body ?: 'Hello {{display_name}},\n\nYou have been assigned the platform role {{role_name}}.\nEffective date: {{effective_date}}.';
        $variables = [
            '{{display_name}}' => $displayName,
            '{{email}}' => (string) $user->email,
            '{{role_name}}' => $roleName,
            '{{effective_date}}' => $effectiveDate ?: 'Immediately',
        ];
        Mail::raw(strtr($body, $variables), function ($message) use ($user, $subject): void {
            $message->to((string) $user->email, (string) ($user->display_name ?: $user->email))->subject($subject);
        });
    }

    public function export(ExportPlatformRolesRequest $request): mixed
    {
        $input = $request->validated();
        $query = $this->filteredQuery(['filter' => $input['filters'] ?? [], 'scope' => $input['scope'] ?? 'filtered', 'selected_ids' => $input['selected_ids'] ?? [], 'sort' => $input['sort'] ?? 'name', 'direction' => $input['direction'] ?? 'asc'])->withCount(['permissions', 'platformUsers', 'platformUsers as users_count']);
        if (($input['delivery'] ?? 'job') !== 'download') {
            $id = DB::table('report_export_jobs')->insertGetId(['report_code' => 'platform_roles', 'format' => 'csv', 'filters' => json_encode($input), 'status' => 'queued', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            return ApiResponse::success(['job_id' => $id, 'status' => 'queued'], 'Platform role export queued.', 202);
        }
        $rows = $query->limit(5001)->get();
        if ($rows->count() > 5000) return ApiResponse::error('Immediate downloads are limited to 5,000 records.', 422, null, 'EXPORT_LIMIT_EXCEEDED');
        $columns = $input['columns'] ?? self::COLUMNS;
        return new StreamedResponse(function () use ($rows, $columns): void {
            $out = fopen('php://output', 'w'); fputcsv($out, $columns);
            foreach ($rows as $row) { $values = []; foreach ($columns as $column) $values[] = $row->{$column}; fputcsv($out, $values); } fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="platform-roles.csv"']);
    }

    private function presentRole(PlatformRole $role): PlatformRole
    {
        $role->load(['permissions', 'platformUsers.department', 'platformUsers.designation', 'platformUsers.teams'])
            ->loadCount(['permissions', 'platformUsers as users_count']);
        $role->setRelation('users', $role->getRelation('platformUsers'));
        $role->unsetRelation('platformUsers');
        return $role;
    }

    private function findRole(string $uuid): ?PlatformRole { return PlatformRole::where('uuid', $uuid)->first(); }
    private function syncPermissions(PlatformRole $role, array $uuids): void { $ids = PlatformPermission::whereIn('uuid', $uuids)->pluck('id')->all(); $role->permissions()->sync($ids); }
    private function setStatus(string $uuid, string $status): mixed { $role = $this->findRole($uuid); if (! $role) return ApiResponse::error('Platform role not found.', 404, null, 'ROLE_NOT_FOUND'); $role->update(['status' => $status]); return ApiResponse::success(['role' => $this->presentRole($role->fresh())], 'Platform role status updated.'); }
    private function filteredQuery(array $input) {
        $query = PlatformRole::query();
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('display_name', 'like', '%'.$input['search'].'%'));
        foreach ($input['filter'] ?? [] as $column => $value) if (in_array($column, ['status','guard_name'], true) && $value !== null && $value !== '') $query->where($column, $value);
        if (($input['filter']['type'] ?? null) === 'system') $query->where('is_system', true);
        if (($input['filter']['type'] ?? null) === 'custom') $query->where('is_system', false);
        if (($input['scope'] ?? null) === 'selected' && ! empty($input['selected_ids'])) $query->whereIn('uuid', $input['selected_ids']);
        return $query->orderBy($input['sort'] ?? 'name', $input['direction'] ?? 'asc');
    }
}
