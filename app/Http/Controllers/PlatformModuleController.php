<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeletePlatformModulesRequest;
use App\Http\Requests\ExportPlatformModulesRequest;
use App\Http\Requests\ImportPlatformModulesRequest;
use App\Http\Requests\ListPlatformModulesRequest;
use App\Http\Requests\ReplacePlatformModuleFeaturesRequest;
use App\Http\Requests\StorePlatformModuleRequest;
use App\Http\Requests\UpdatePlatformModuleRequest;
use App\Models\Feature;
use App\Models\Module;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformModuleController extends Controller
{
    private const EXPORT_COLUMNS = ['uuid', 'name', 'code', 'description', 'icon', 'category', 'is_core', 'status', 'sort_order', 'features_count', 'enabled_tenants_count', 'created_at', 'updated_at'];

    public function index(ListPlatformModulesRequest $request): mixed
    {
        $input = $request->validated();
        $query = Module::query()->with(['features.plans', 'tenantOverrides.tenant'])->withCount('features');
        if (! empty($input['search'])) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('code', 'like', '%'.$input['search'].'%'));
        }
        foreach (['status', 'category'] as $column) {
            if (($input[$column] ?? null) !== null && $input[$column] !== '') $query->where($column, $input[$column]);
        }
        $query->orderBy('sort_order')->orderBy('name');
        $paginator = $query->paginate($request->integer('per_page', 10))->withQueryString();
        $featureModules = Feature::query()->select('module')->distinct()->orderBy('module')->pluck('module')->values();
        $statistics = [
            'total' => Module::count(),
            'active' => Module::where('status', 'active')->count(),
            'inactive' => Module::where('status', 'inactive')->count(),
            'core' => Module::where('is_core', true)->count(),
        ];

        return ApiResponse::success($paginator->items(), 'Platform modules fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
            'feature_modules' => $featureModules, 'statistics' => $statistics,
        ]);
    }

    public function store(StorePlatformModuleRequest $request): mixed
    {
        $module = Module::create($request->validated() + ['uuid' => (string) Str::uuid()]);
        return ApiResponse::success(['module' => $this->present($module)], 'Platform module created successfully.', 201);
    }

    public function show(string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        return ApiResponse::success(['module' => $this->present($module)], 'Platform module fetched.');
    }

    public function update(UpdatePlatformModuleRequest $request, string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        $module->update($request->validated());
        return ApiResponse::success(['module' => $this->present($module->fresh())], 'Platform module updated successfully.');
    }

    public function destroy(string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        $blocked = $this->deletionBlockReason($module);
        if ($blocked !== null) return ApiResponse::error($blocked, 409, null, 'MODULE_IN_USE');
        $module->delete();
        return ApiResponse::success(null, 'Platform module deleted successfully.');
    }

    public function enable(string $moduleUuid): mixed { return $this->setStatus($moduleUuid, 'active'); }
    public function disable(string $moduleUuid): mixed { return $this->setStatus($moduleUuid, 'inactive'); }

    public function features(string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        return ApiResponse::success(['module' => $this->present($module), 'features' => $module->features()->orderBy('name')->get()->load(['plans'])->loadCount('plans')], 'Module features fetched.');
    }

    public function tenants(string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        $tenants = $module->tenantOverrides()->with('tenant')->orderBy('id')->get()->map(function ($override) {
            $tenant = $override->tenant;
            return ['uuid' => $tenant?->uuid, 'organization_name' => $tenant?->organization_name, 'slug' => $tenant?->slug, 'enabled' => $override->enabled, 'limits' => $override->limits];
        })->filter(fn ($tenant) => filled($tenant['uuid']))->values();
        return ApiResponse::success(['module' => $module, 'tenants' => $tenants], 'Module tenants fetched.');
    }

    public function replaceFeatures(ReplacePlatformModuleFeaturesRequest $request, string $moduleUuid): mixed
    {
        $module = $this->findModule($moduleUuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        $uuids = $request->validated('feature_uuids');
        DB::transaction(function () use ($module, $uuids): void {
            Feature::where('module', $module->code)->whereNotIn('uuid', $uuids)->update(['module' => 'unassigned']);
            if ($uuids !== []) Feature::whereIn('uuid', $uuids)->update(['module' => $module->code]);
        });
        return ApiResponse::success(['module' => $this->present($module->fresh()), 'features' => $module->features()->orderBy('name')->get()], 'Module features replaced.');
    }

    public function export(ExportPlatformModulesRequest $request): mixed
    {
        $input = $request->validated();
        $id = DB::table('report_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(), 'report_code' => 'platform_modules', 'format' => 'csv',
            'filters' => json_encode($input), 'status' => 'queued', 'created_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return ApiResponse::success(['export' => ['job_id' => $id, 'status' => 'queued', 'format' => 'csv']], 'Platform module export queued.', 202);
    }

    public function import(ImportPlatformModulesRequest $request): mixed
    {
        return ApiResponse::success(['import' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Platform module import queued.', 202);
    }

    public function bulkDestroy(BulkDeletePlatformModulesRequest $request): mixed
    {
        $deleted = 0; $skipped = 0; $skippedModules = [];
        foreach ($request->validated('module_uuids') as $uuid) {
            $module = $this->findModule($uuid);
            if (! $module) { $skipped++; $skippedModules[] = ['uuid' => $uuid, 'reason' => 'not_found']; continue; }
            $reason = $this->deletionBlockReason($module);
            if ($reason !== null) { $skipped++; $skippedModules[] = ['uuid' => $uuid, 'reason' => $reason]; continue; }
            $module->delete(); $deleted++;
        }
        return ApiResponse::success(['deleted' => $deleted, 'skipped' => $skipped, 'skipped_modules' => $skippedModules], 'Platform module bulk deletion processed.');
    }

    private function findModule(string $uuid): ?Module { return Module::where('uuid', $uuid)->first(); }

    private function present(Module $module): Module
    {
        return $module->load(['features.plans', 'tenantOverrides', 'activityLogs' => fn ($q) => $q->latest('created_at')->limit(25)])->loadCount(['features', 'tenantOverrides'])->setAttribute('enabled_tenants_count', $module->tenantOverrides()->where('enabled', true)->distinct('tenant_id')->count('tenant_id'));
    }

    private function deletionBlockReason(Module $module): ?string
    {
        if ($module->is_core) return 'Core modules cannot be deleted.';
        if ($module->features()->exists()) return 'Module has assigned features.';
        if ($module->tenantOverrides()->exists()) return 'Module has tenant overrides.';
        return null;
    }

    private function setStatus(string $uuid, string $status): mixed
    {
        $module = $this->findModule($uuid);
        if (! $module) return ApiResponse::error('Platform module not found.', 404, null, 'MODULE_NOT_FOUND');
        $module->update(['status' => $status]);
        return ApiResponse::success(['module' => $this->present($module->fresh())], 'Platform module status updated.');
    }
}
