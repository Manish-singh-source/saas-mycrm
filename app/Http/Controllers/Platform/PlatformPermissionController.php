<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Models\PlatformPermission;
use App\Services\Rbac\RbacAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformPermissionController extends BaseApiController
{
    private const SORTS = ['module', 'name', 'display_name', 'status', 'created_at', 'updated_at'];
    private const EXPORT_COLUMNS = ['uuid', 'module', 'name', 'display_name', 'guard_name', 'description', 'is_system', 'status', 'roles_count', 'created_at', 'updated_at'];

    public function __construct(private readonly RbacAuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $this->applySorting($query, $request);
        $page = $query->paginate((int) $request->integer('per_page', 50));

        return $this->list($page->items(), $page);
    }

    public function export(Request $request)
    {
        $data = $this->exportData($request);
        if (($data['delivery'] ?? 'job') === 'download') {
            $rows = $this->filteredQuery($request);
            $this->applySorting($rows, $request);
            $records = $rows->limit(5000)->get()->map(fn (PlatformPermission $permission) => $permission->toArray())->all();
            $csv = $this->csv($records, $data['columns']);

            return $this->success([
                'download' => [
                    'filename' => 'platform-permissions-'.now()->format('YmdHis').'.csv',
                    'mime_type' => 'text/csv',
                    'size_bytes' => strlen($csv),
                    'content' => $csv,
                ],
            ], 'Platform permissions export ready.', 201);
        }

        $job = $this->createExportJob('platform-permissions', $request, $data);

        return $this->success(['export' => $job], 'Platform permissions export queued.', 201);
    }

    public function grouped()
    {
        return $this->success(['permissions' => $this->group(PlatformPermission::query()->where('status', 'active')->orderBy('module')->orderBy('name')->get())]);
    }

    public function store(Request $request)
    {
        $permission = PlatformPermission::query()->create($this->data($request));
        $this->audit->log($request, 'platform_permission_created', $permission, null, $permission->toArray());

        return $this->success(['permission' => $permission], 'Permission created.', 201);
    }

    public function show($id)
    {
        return $this->success(['permission' => PlatformPermission::query()->where('uuid', $id)->withCount('roles')->firstOrFail()]);
    }

    public function update(Request $request, $id)
    {
        $permission = PlatformPermission::query()->where('uuid', $id)->firstOrFail();
        if ($permission->is_system && $request->filled('name') && $request->input('name') !== $permission->name) {
            return $this->businessError('System permissions cannot be renamed.', 'SYSTEM_PERMISSION_RENAME_FORBIDDEN', 403);
        }
        $old = $permission->toArray();
        $permission->fill($this->data($request, $permission))->save();
        $this->audit->log($request, 'platform_permission_updated', $permission, $old, $permission->fresh()->toArray());

        return $this->success(['permission' => $permission->fresh()], 'Permission updated.');
    }

    public function destroy(Request $request, $id)
    {
        $permission = PlatformPermission::query()->where('uuid', $id)->firstOrFail();
        if ($permission->is_system) {
            return $this->businessError('System permissions cannot be deleted.', 'SYSTEM_PERMISSION_DELETE_FORBIDDEN', 403);
        }
        if ($permission->roles()->exists()) {
            return $this->businessError('Assigned permissions cannot be deleted.', 'PERMISSION_IN_USE', 409);
        }

        $old = $permission->toArray();
        $permission->delete();
        $this->audit->log($request, 'platform_permission_deleted', $permission, $old, null);

        return $this->success(null, 'Permission deleted.');
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = PlatformPermission::query()->withCount('roles');
        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(fn ($x) => $x->where('name', 'like', '%'.$search.'%')->orWhere('display_name', 'like', '%'.$search.'%')->orWhere('module', 'like', '%'.$search.'%'));
        }
        foreach (['module', 'guard_name', 'status'] as $field) {
            if ($request->filled('filter.'.$field)) {
                $query->where($field, $request->input('filter.'.$field));
            }
        }
        $selected = $this->selectedIds($request);
        if ($selected !== [] && $request->input('scope') === 'selected') {
            $query->whereIn('uuid', $selected);
        }

        return $query;
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sort = (string) $request->input('sort', 'module');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'module';
        }
        $query->orderBy($sort, $direction)->orderBy($sort === 'name' ? 'module' : 'name')->orderBy('id');
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
            $lines[] = implode(',', array_map(fn ($column) => $this->csvValue($record[$column] ?? null), $columns));
        }
        return implode("\n", $lines)."\n";
    }

    private function csvValue(mixed $value): string
    {
        if (is_bool($value)) $value = $value ? '1' : '0';
        if ($value === null) $value = '';
        if (is_array($value) || is_object($value)) $value = json_encode($value);
        return '"'.str_replace('"', '""', (string) $value).'"';
    }

    private function data(Request $request, ?PlatformPermission $permission = null): array
    {
        $guard = (string) $request->input('guard_name', $permission?->guard_name ?? 'platform');
        return $request->validate([
            'module' => [$permission ? 'sometimes' : 'required', 'string', 'max:100'],
            'name' => [$permission ? 'sometimes' : 'required', 'string', 'max:150', Rule::unique('platform_permissions')->where('guard_name', $guard)->ignore($permission?->id)],
            'display_name' => ['nullable', 'string', 'max:150'],
            'guard_name' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_system' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function group($permissions)
    {
        return $permissions->groupBy('module')->map(fn ($items) => $items->values()->all())->all();
    }
}
