<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformModuleController extends BasePlatformController
{
    public function __construct(private readonly PlatformBillingCatalogService $billing) {}

    public function index(Request $request)
    {
        $q = DB::table('modules');
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        if ($request->filled('category')) $q->where('category', $request->input('category'));
        $p = $q->orderBy('sort_order')->orderBy('name')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p, 'OK', ['feature_modules' => DB::table('features')->select('module')->distinct()->orderBy('module')->pluck('module')]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $id = DB::table('modules')->insertGetId([...$data, 'uuid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $module = DB::table('modules')->where('id', $id)->first();
        $this->billing->audit($request, 'module_created', 'modules', $id, null, (array) $module);
        return $this->success(['module' => $module], 'Module created.', 201);
    }

    public function show(string $module_uuid)
    {
        $module = $this->billing->byUuid('modules', $module_uuid);
        return $this->success(['module' => $module, ...$this->relations($module)]);
    }

    public function update(Request $request, string $module_uuid)
    {
        $module = $this->billing->byUuid('modules', $module_uuid);
        $data = $this->data($request, $module->id, true);
        DB::table('modules')->where('id', $module->id)->update([...$data, 'updated_at' => now()]);
        $fresh = DB::table('modules')->where('id', $module->id)->first();
        $this->billing->audit($request, 'module_updated', 'modules', $module->id, (array) $module, (array) $fresh);
        return $this->success(['module' => $fresh], 'Module updated.');
    }

    public function enable(Request $request, string $module_uuid) { return $this->status($request, $module_uuid, 'active'); }
    public function disable(Request $request, string $module_uuid) { return $this->status($request, $module_uuid, 'inactive'); }

    public function features(string $module_uuid)
    {
        $module = $this->billing->byUuid('modules', $module_uuid);
        return $this->success(['features' => DB::table('features')->where('module', $module->code)->orderBy('name')->get()]);
    }

    public function replaceFeatures(Request $request, string $module_uuid)
    {
        $module = $this->billing->byUuid('modules', $module_uuid);
        $data = $request->validate(['feature_uuids' => ['required', 'array'], 'feature_uuids.*' => ['uuid']]);
        foreach ($data['feature_uuids'] as $uuid) {
            $feature = $this->billing->byUuid('features', $uuid);
            DB::table('features')->where('id', $feature->id)->update(['module' => $module->code, 'updated_at' => now()]);
        }
        $this->billing->audit($request, 'module_features_replaced', 'modules', $module->id, null, $data);
        return $this->success(['features' => DB::table('features')->where('module', $module->code)->orderBy('name')->get()], 'Module features updated.');
    }

    public function tenants(string $module_uuid)
    {
        $module = $this->billing->byUuid('modules', $module_uuid);
        return $this->success(['tenants' => DB::table('tenant_module_overrides')->join('tenants', 'tenants.id', '=', 'tenant_module_overrides.tenant_id')->where('module_code', $module->code)->select('tenants.uuid', 'tenants.organization_name', 'tenants.slug', 'tenant_module_overrides.enabled', 'tenant_module_overrides.limits')->get()]);
    }

    public function tenantModules(string $tenant_uuid)
    {
        $tenantId = $this->billing->tenantId($tenant_uuid);
        return $this->success(['modules' => DB::table('modules')->leftJoin('tenant_module_overrides', fn ($join) => $join->on('tenant_module_overrides.module_code', '=', 'modules.code')->where('tenant_module_overrides.tenant_id', '=', $tenantId))->select('modules.*', 'tenant_module_overrides.enabled as tenant_enabled', 'tenant_module_overrides.limits as tenant_limits')->orderBy('modules.sort_order')->get()]);
    }

    public function overrideTenantModule(Request $request, string $tenant_uuid, string $module_code)
    {
        $tenantId = $this->billing->tenantId($tenant_uuid);
        $data = $request->validate(['enabled' => ['required', 'boolean'], 'limits' => ['nullable', 'array'], 'metadata' => ['nullable', 'array'], 'reason' => ['nullable', 'string']]);
        DB::table('tenant_module_overrides')->updateOrInsert(['tenant_id' => $tenantId, 'module_code' => $module_code], ['uuid' => (string) Str::uuid(), 'enabled' => $data['enabled'], 'limits' => isset($data['limits']) ? json_encode($data['limits']) : null, 'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->billing->audit($request, 'tenant_module_override_updated', 'tenant_module_overrides', $tenantId, null, $data, $data['reason'] ?? null);
        return $this->success(['override' => DB::table('tenant_module_overrides')->where('tenant_id', $tenantId)->where('module_code', $module_code)->first()], 'Tenant module override updated.');
    }

    private function status(Request $request, string $uuid, string $status)
    {
        $module = $this->billing->byUuid('modules', $uuid);
        DB::table('modules')->where('id', $module->id)->update(['status' => $status, 'updated_at' => now()]);
        $this->billing->audit($request, 'module_'.$status, 'modules', $module->id, (array) $module, ['status' => $status]);
        return $this->success(['module' => DB::table('modules')->where('id', $module->id)->first()], 'Module status updated.');
    }

    private function relations(object $module): array
    {
        return [
            'features' => DB::table('features')->where('module', $module->code)->orderBy('name')->get(),
            'enabled_tenant_count' => DB::table('tenant_module_overrides')->where('module_code', $module->code)->where('enabled', true)->count(),
        ];
    }

    private function data(Request $request, ?int $id = null, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('modules', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_core' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }
}
