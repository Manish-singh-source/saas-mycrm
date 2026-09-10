<?php
namespace App\Http\Controllers;

use App\Http\Requests\ExportPlatformPermissionsRequest;
use App\Http\Requests\ListPlatformPermissionsRequest;
use App\Http\Requests\StorePlatformPermissionRequest;
use App\Http\Requests\UpdatePlatformPermissionRequest;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PlatformPermissionController extends Controller
{
    private const COLUMNS = ['uuid', 'module', 'name', 'display_name', 'guard_name', 'description', 'is_system', 'status', 'roles_count', 'created_at', 'updated_at'];

    public function grouped(): mixed
    {
        $permissions = PlatformPermission::with(['roles', 'roleAssignments', 'modelAssignments'])
            ->where('status', 'active')->orderBy('module')->orderBy('name')->get();

        return ApiResponse::success($permissions->groupBy('module'), 'Grouped platform permissions fetched.');
    }

    public function index(ListPlatformPermissionsRequest $request): mixed
    {
        $baseQuery = $this->filteredQuery($request->validated());
        $kpiRows = (clone $baseQuery)->withCount('roles')->get();
        $paginator = (clone $baseQuery)
            ->with(['roles'])
            ->withCount('roles')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $kpis = [
            'total' => $kpiRows->count(),
            'active' => $kpiRows->where('status', 'active')->count(),
            'inactive' => $kpiRows->where('status', 'inactive')->count(),
            'system' => $kpiRows->where('is_system', true)->count(),
            'custom' => $kpiRows->where('is_system', false)->count(),
            'assignments' => $kpiRows->sum('roles_count'),
        ];

        return ApiResponse::success($paginator->items(), 'Platform permissions fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
            'kpis' => $kpis,
        ]);
    }

    public function store(StorePlatformPermissionRequest $request): mixed
    {
        $data = $request->validated();
        $permission = new PlatformPermission();
        $permission->forceFill(array_merge(
            ['uuid' => (string) Str::uuid(), 'guard_name' => 'platform', 'status' => 'active', 'is_system' => false],
            collect($data)->except(['role_uuids', 'role_ids'])->all()
        ))->save();

        if (array_key_exists('role_uuids', $data) || array_key_exists('role_ids', $data)) {
            $permission->roles()->sync($this->roleIds($data));
        }

        return ApiResponse::success(['permission' => $this->load($permission)], 'Platform permission created successfully.', 201);
    }

    public function show(string $permissionUuid): mixed
    {
        $permission = $this->find($permissionUuid);
        if (! $permission) return ApiResponse::error('Platform permission not found.', 404, null, 'PERMISSION_NOT_FOUND');

        return ApiResponse::success(['permission' => $this->load($permission)], 'Platform permission fetched.');
    }

    public function update(UpdatePlatformPermissionRequest $request, string $permissionUuid): mixed
    {
        $permission = $this->find($permissionUuid);
        if (! $permission) return ApiResponse::error('Platform permission not found.', 404, null, 'PERMISSION_NOT_FOUND');

        $data = $request->validated();
        if ($permission->is_system && array_key_exists('name', $data) && $data['name'] !== $permission->name) {
            return ApiResponse::error('System permissions cannot be renamed.', 403, null, 'SYSTEM_PERMISSION_RENAME_FORBIDDEN');
        }

        $permission->forceFill(collect($data)->except(['role_uuids', 'role_ids', 'is_system'])->all())->save();
        if (array_key_exists('role_uuids', $data) || array_key_exists('role_ids', $data)) {
            $permission->roles()->sync($this->roleIds($data));
        }

        return ApiResponse::success(['permission' => $this->load($permission)], 'Platform permission updated successfully.');
    }

    public function destroy(string $permissionUuid): mixed
    {
        $permission = $this->find($permissionUuid);
        if (! $permission) return ApiResponse::error('Platform permission not found.', 404, null, 'PERMISSION_NOT_FOUND');
        if ($permission->is_system) return ApiResponse::error('System permissions cannot be deleted.', 403, null, 'SYSTEM_PERMISSION_DELETE_FORBIDDEN');
        if ($permission->roles()->exists()) return ApiResponse::error('Permission is assigned to one or more roles.', 409, null, 'PERMISSION_IN_USE');

        $permission->delete();

        return ApiResponse::success(null, 'Platform permission deleted successfully.');
    }

    public function export(ExportPlatformPermissionsRequest $request): mixed
    {
        $input = $request->validated();
        $query = $this->filteredQuery(['search' => null, 'filter' => $input['filters'] ?? [], 'scope' => $input['scope'] ?? 'filtered', 'selected_ids' => $input['selected_ids'] ?? [], 'sort' => $input['sort'] ?? 'module', 'direction' => $input['direction'] ?? 'asc'])->withCount('roles');

        if (($input['delivery'] ?? 'job') !== 'download') {
            $id = DB::table('report_export_jobs')->insertGetId(['report_code' => 'platform_permissions', 'format' => 'csv', 'filters' => json_encode($input), 'status' => 'queued', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            return ApiResponse::success(['job_id' => $id, 'status' => 'queued'], 'Platform permission export queued.', 202);
        }

        $rows = $query->limit(5001)->get();
        if ($rows->count() > 5000) return ApiResponse::error('Immediate downloads are limited to 5,000 records.', 422, null, 'EXPORT_LIMIT_EXCEEDED');
        $columns = $input['columns'] ?? self::COLUMNS;

        return new StreamedResponse(function () use ($rows, $columns): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $column) $values[] = $column === 'roles_count' ? $row->roles_count : $row->{$column};
                fputcsv($out, $values);
            }
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="platform-permissions.csv"']);
    }

    private function find(string $uuid): ?PlatformPermission
    {
        return PlatformPermission::where('uuid', $uuid)->first();
    }

    private function load(PlatformPermission $permission): PlatformPermission
    {
        return $permission->fresh()->load(['roles', 'roleAssignments', 'modelAssignments'])->loadCount('roles');
    }

    private function roleIds(array $input): array
    {
        if (array_key_exists('role_uuids', $input)) return PlatformRole::whereIn('uuid', $input['role_uuids'] ?? [])->pluck('id')->all();
        return $input['role_ids'] ?? [];
    }

    private function filteredQuery(array $input)
    {
        $query = PlatformPermission::query();
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('display_name', 'like', '%'.$input['search'].'%')->orWhere('module', 'like', '%'.$input['search'].'%'));
        foreach ($input['filter'] ?? [] as $column => $value) if (in_array($column, ['module', 'guard_name', 'status'], true) && $value !== null && $value !== '') $query->where($column, $value);
        if (($input['scope'] ?? null) === 'selected' && ! empty($input['selected_ids'])) $query->whereIn('uuid', $input['selected_ids']);
        $sort = ($input['sort'] ?? 'module') === 'created' ? 'created_at' : ($input['sort'] ?? 'module');

        return $query->orderBy($sort, $input['direction'] ?? 'asc');
    }
}
