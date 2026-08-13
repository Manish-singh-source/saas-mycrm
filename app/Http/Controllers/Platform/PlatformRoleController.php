<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Services\Rbac\RbacAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformRoleController extends BaseApiController
{
    private const SORTS = ['name', 'display_name', 'status', 'created_at', 'updated_at'];
    private const EXPORT_COLUMNS = ['uuid', 'name', 'display_name', 'guard_name', 'is_system', 'status', 'permissions_count', 'users_count', 'created_at', 'updated_at'];

    public function __construct(private readonly RbacAuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $this->applySorting($query, $request);

        $page = $query->paginate((int) $request->integer('per_page', 25));

        return $this->list($page->items(), $page);
    }

    public function export(Request $request)
    {
        $data = $this->exportData($request);
        if (($data['delivery'] ?? 'job') === 'download') {
            $rows = $this->filteredQuery($request);
            $this->applySorting($rows, $request);
            $records = $rows->limit(5000)->get()->map(fn(PlatformRole $role) => $role->toArray())->all();
            $csv = $this->csv($records, $data['columns']);

            return $this->success([
                'download' => [
                    'filename' => 'platform-roles-' . now()->format('YmdHis') . '.csv',
                    'mime_type' => 'text/csv',
                    'size_bytes' => strlen($csv),
                    'content' => $csv,
                ],
            ], 'Platform roles export ready.', 201);
        }

        $job = $this->createExportJob('platform-roles', $request, $data);

        return $this->success(['export' => $job], 'Platform roles export queued.', 201);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $permissionIds = $this->permissionIds($data['permission_ids'] ?? []);
        unset($data['permission_ids'], $data['audit_reason']);

        $role = PlatformRole::query()->create($data);
        $role->permissions()->sync($permissionIds);
        $this->audit->log($request, 'platform_role_created', $role, null, ['permission_ids' => $permissionIds], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role created.', 201);
    }

    public function show($role_uuid)
    {
        return $this->success(['role' => $this->payload($this->findRole($role_uuid), true, true)]);
    }

    public function update(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $this->validatedData($request, $role);
        if ($role->is_system && isset($data['name']) && $data['name'] !== $role->name) {
            return $this->businessError('System roles cannot be renamed.', 'SYSTEM_ROLE_RENAME_FORBIDDEN', 403);
        }

        $old = $this->snapshot($role);
        $permissionIds = array_key_exists('permission_ids', $data) ? $this->permissionIds($data['permission_ids']) : null;
        unset($data['permission_ids'], $data['audit_reason']);
        $role->fill($data)->save();
        if ($permissionIds !== null) {
            $role->permissions()->sync($permissionIds);
        }
        $this->audit->log($request, 'platform_role_updated', $role, $old, $this->snapshot($role), $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true, true)], 'Role updated.');
    }

    public function destroy(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        if ($role->is_system) {
            return $this->businessError('System roles cannot be deleted.', 'SYSTEM_ROLE_DELETE_FORBIDDEN', 403);
        }
        if ($role->users()->exists()) {
            return $this->businessError('Assigned roles cannot be deleted.', 'ROLE_IN_USE', 409);
        }

        $old = $this->snapshot($role);
        $role->permissions()->detach();
        $role->delete();
        $this->audit->log($request, 'platform_role_deleted', $role, $old, null, $request->input('audit_reason'));

        return $this->success(null, 'Role deleted.');
    }

    public function clone(Request $request, $role_uuid)
    {
        $source = $this->findRole($role_uuid);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('platform_roles')->where('guard_name', $source->guard_name)],
            'display_name' => ['required', 'string', 'max:150'],
            'copy_permissions' => ['sometimes', 'boolean'],
            'copy_description' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);
        $role = PlatformRole::query()->create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'guard_name' => $source->guard_name,
            'description' => ($data['copy_description'] ?? true) ? $source->description : null,
            'is_system' => false,
            'status' => $data['status'] ?? 'inactive',
        ]);
        if ($data['copy_permissions'] ?? true) {
            $role->permissions()->sync($source->permissions()->pluck('platform_permissions.id')->all());
        }
        $this->audit->log($request, 'platform_role_cloned', $role, null, ['source_role_id' => $source->id], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role cloned.', 201);
    }

    public function activate(Request $request, $role_uuid)
    {
        return $this->setStatus($request, $role_uuid, 'active');
    }
    public function deactivate(Request $request, $role_uuid)
    {
        return $this->setStatus($request, $role_uuid, 'inactive');
    }

    public function permissions($role_uuid)
    {
        return $this->success(['permissions' => $this->grouped($this->findRole($role_uuid)->permissions()->orderBy('module')->orderBy('name')->get())]);
    }

    public function syncPermissions(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $request->validate(['permission_ids' => ['required', 'array', 'min:1'], 'permission_ids.*' => ['required', 'string'], 'audit_reason' => ['nullable', 'string', 'max:500']]);
        $old = ['permission_ids' => $role->permissions()->pluck('platform_permissions.id')->all()];
        $permissionIds = $this->permissionIds($data['permission_ids']);
        $role->permissions()->sync($permissionIds);
        $this->audit->log($request, 'platform_role_permissions_synced', $role, $old, ['permission_ids' => $permissionIds], $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh(), true)], 'Role permissions updated.');
    }

    public function users($role_uuid)
    {
        return $this->success(['users' => $this->findRole($role_uuid)->users()->orderBy('display_name')->get(['platform_users.uuid', 'display_name', 'email', 'department', 'status'])]);
    }

    public function assignUsers(Request $request, $role_uuid)
    {
        $role = $this->findRole($role_uuid);
        $data = $request->validate(['platform_user_ids' => ['required', 'array'], 'platform_user_ids.*' => ['required', 'string'], 'audit_reason' => ['nullable', 'string', 'max:500']]);
        $userIds = PlatformUser::query()->whereIn('uuid', $data['platform_user_ids'])->pluck('id')->all();
        foreach ($userIds as $userId) {
            DB::table('platform_model_has_roles')->updateOrInsert(['role_id' => $role->id, 'model_id' => $userId, 'model_type' => PlatformUser::class], []);
        }
        $this->audit->log($request, 'platform_role_users_assigned', $role, null, ['user_ids' => $userIds], $request->input('audit_reason'));

        return $this->success(['users' => $role->users()->count()], 'Users assigned.');
    }

    public function removeUser(Request $request, $role_uuid, $platform_user_uuid)
    {
        $role = $this->findRole($role_uuid);
        $user = PlatformUser::query()->where('uuid', $platform_user_uuid)->firstOrFail();
        DB::table('platform_model_has_roles')->where(['role_id' => $role->id, 'model_id' => $user->id, 'model_type' => PlatformUser::class])->delete();
        $this->audit->log($request, 'platform_role_user_removed', $role, ['user_id' => $user->id], null, $request->input('audit_reason'));

        return $this->success(null, 'User removed from role.');
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = PlatformRole::query()->withCount(['permissions', 'users']);
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn($x) => $x->where('name', 'like', '%' . $search . '%')->orWhere('display_name', 'like', '%' . $search . '%'));
        }
        foreach (['status', 'guard_name'] as $field) {
            if ($request->filled('filter.' . $field)) {
                $query->where($field, $request->input('filter.' . $field));
            }
        }
        if ($request->input('filter.type') === 'system') {
            $query->where('is_system', true);
        }
        if ($request->input('filter.type') === 'custom') {
            $query->where('is_system', false);
        }
        $selected = $this->selectedIds($request);
        if ($selected !== [] && $request->input('scope') === 'selected') {
            $query->whereIn('uuid', $selected);
        }

        return $query;
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sort = (string) $request->input('sort', 'name');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'name';
        }
        $query->orderBy($sort, $direction)->orderBy('id');
    }

    private function exportData(Request $request): array
    {
        $data = $request->validate([
            'format' => ['nullable', Rule::in(['csv'])],
            'delivery' => ['nullable', Rule::in(['job', 'download'])],
            'scope' => ['nullable', Rule::in(['filtered', 'selected'])],
            'filters' => ['nullable', 'array'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['string'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'email_when_ready' => ['nullable', 'boolean'],
        ]);

        return [
            ...$data,
            'format' => $data['format'] ?? 'csv',
            'delivery' => $data['delivery'] ?? 'job',
            'scope' => $data['scope'] ?? 'filtered',
            'columns' => $this->columns($data['columns'] ?? []),
        ];
    }

    private function columns(array $requested): array
    {
        $columns = array_values(array_intersect($requested, self::EXPORT_COLUMNS));
        return $columns !== [] ? $columns : self::EXPORT_COLUMNS;
    }

    private function selectedIds(Request $request): array
    {
        $ids = $request->input('selected_ids', []);
        return is_array($ids) ? array_values(array_filter($ids)) : [];
    }

    private function createExportJob(string $code, Request $request, array $payload): object
    {
        $id = DB::table('report_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'report_code' => $code,
            'format' => $payload['format'] ?? 'csv',
            'filters' => json_encode([...$request->query(), ...$payload]),
            'status' => 'queued',
            'created_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('report_export_jobs')->where('id', $id)->first();
    }

    private function csv(array $records, array $columns): string
    {
        $lines = [implode(',', $columns)];
        foreach ($records as $record) {
            $lines[] = implode(',', array_map(fn($column) => $this->csvValue($record[$column] ?? null), $columns));
        }
        return implode("\n", $lines) . "\n";
    }

    private function csvValue(mixed $value): string
    {
        if (is_bool($value)) $value = $value ? '1' : '0';
        if ($value === null) $value = '';
        if (is_array($value) || is_object($value)) $value = json_encode($value);
        return '"' . str_replace('"', '""', (string) $value) . '"';
    }

    private function setStatus(Request $request, $role_uuid, $status)
    {
        $role = $this->findRole($role_uuid);
        $old = $this->snapshot($role);
        $role->forceFill(['status' => $status])->save();
        $this->audit->log($request, 'platform_role_' . $status, $role, $old, $this->snapshot($role), $request->input('audit_reason'));

        return $this->success(['role' => $this->payload($role->fresh())], 'Role ' . $status . '.');
    }

    private function findRole($uuid)
    {
        return PlatformRole::query()->where('uuid', $uuid)->firstOrFail();
    }
    private function permissionIds(array $uuids)
    {
        return PlatformPermission::query()->whereIn('uuid', $uuids)->pluck('id')->all();
    }
    private function validatedData(Request $request, ?PlatformRole $role = null)
    {
        $guard = (string) $request->input('guard_name', $role?->guard_name ?? 'platform');
        return $request->validate([
            'name' => [$role ? 'sometimes' : 'required', 'string', 'max:150', Rule::unique('platform_roles')->where('guard_name', $guard)->ignore($role?->id)],
            'display_name' => [$role ? 'sometimes' : 'required', 'string', 'max:150'],
            'guard_name' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'permission_ids' => ['sometimes', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'string'],
            'audit_reason' => ['nullable', 'string', 'max:500'],
        ]);
    }
    private function payload(PlatformRole $role, $withPermissions = false, $withUsers = false)
    {
        $data = $role->loadCount(['permissions', 'users'])->toArray();
        if ($withPermissions) $data['permissions'] = $this->grouped($role->permissions()->orderBy('module')->orderBy('name')->get());
        if ($withUsers) $data['users'] = $role->users()->orderBy('display_name')->get(['platform_users.uuid', 'display_name', 'email', 'department', 'status'])->all();
        return $data;
    }
    private function grouped($permissions)
    {
        return $permissions->groupBy('module')->map(fn($items) => $items->values()->map->only(['uuid', 'module', 'name', 'display_name', 'description', 'guard_name', 'is_system', 'status'])->all())->all();
    }
    private function snapshot(PlatformRole $role)
    {
        return $role->fresh()->load('permissions')->toArray();
    }
}
