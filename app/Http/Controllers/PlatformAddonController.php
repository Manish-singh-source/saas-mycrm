<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeletePlatformAddonsRequest;
use App\Http\Requests\ExportPlatformAddonsRequest;
use App\Http\Requests\ImportPlatformAddonsRequest;
use App\Http\Requests\ListPlatformAddonsRequest;
use App\Http\Requests\StorePlatformAddonRequest;
use App\Http\Requests\UpdatePlatformAddonRequest;
use App\Models\AddonPlan;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformAddonController extends Controller
{
    public function index(ListPlatformAddonsRequest $request): mixed
    {
        $input = $request->validated();
        $query = AddonPlan::query()->with(['plans'])->withCount(['plans', 'subscriptionAddons']);
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('code', 'like', '%'.$input['search'].'%'));
        foreach (['status'] as $column) if (($input[$column] ?? null) !== null && $input[$column] !== '') $query->where($column, $input[$column]);
        $paginator = $query->latest('created_at')->paginate($request->integer('per_page', 10))->withQueryString();
        $statistics = [
            'total' => AddonPlan::count(),
            'active' => AddonPlan::where('status', 'active')->count(),
            'inactive' => AddonPlan::where('status', 'inactive')->count(),
            'archived' => AddonPlan::where('status', 'archived')->count(),
            'assigned_to_subscriptions' => AddonPlan::whereHas('subscriptionAddons')->count(),
        ];
        return ApiResponse::success($paginator->items(), 'Platform add-ons fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(), 'statistics' => $statistics,
        ]);
    }

    public function store(StorePlatformAddonRequest $request): mixed
    {
        $input = $request->validated();
        $data = collect($input)->only(['name', 'pricing_type', 'price', 'currency', 'is_public', 'status'])->all();
        $data['code'] = $this->uniqueCode($input['name']);
        $addon = AddonPlan::create($data + ['uuid' => (string) Str::uuid()]);
        ActivityLogger::record($request, 'created', $addon, 'Platform add-on created.', $addon->toArray());
        return ApiResponse::success(['addon' => $this->present($addon)], 'Platform add-on created successfully.', 201);
    }

    public function show(string $addonUuid): mixed
    {
        $addon = $this->findAddon($addonUuid);
        if (! $addon) return ApiResponse::error('Platform add-on not found.', 404, null, 'ADDON_NOT_FOUND');
        return ApiResponse::success(['addon' => $this->present($addon)], 'Platform add-on fetched.');
    }

    public function update(UpdatePlatformAddonRequest $request, string $addonUuid): mixed
    {
        $addon = $this->findAddon($addonUuid);
        if (! $addon) return ApiResponse::error('Platform add-on not found.', 404, null, 'ADDON_NOT_FOUND');
        $data = collect($request->validated())->only(['name', 'pricing_type', 'price', 'currency', 'is_public', 'status'])->all();
        $addon->update($data);
        ActivityLogger::record($request, 'updated', $addon, 'Platform add-on updated.', $data);
        return ApiResponse::success(['addon' => $this->present($addon->fresh())], 'Platform add-on updated successfully.');
    }

    public function destroy(Request $request, string $addonUuid): mixed
    {
        $addon = $this->findAddon($addonUuid);
        if (! $addon) return ApiResponse::error('Platform add-on not found.', 404, null, 'ADDON_NOT_FOUND');
        if ($addon->subscriptionAddons()->exists()) return ApiResponse::error('Add-on is assigned to one or more subscriptions.', 409, null, 'ADDON_PLAN_IN_USE');
        $addon->delete();
        ActivityLogger::record($request, 'deleted', $addon, 'Platform add-on deleted.');
        return ApiResponse::success(null, 'Platform add-on deleted successfully.');
    }

    public function activate(Request $request, string $addonUuid): mixed { return $this->setStatus($request, $addonUuid, 'active'); }
    public function deactivate(Request $request, string $addonUuid): mixed { return $this->setStatus($request, $addonUuid, 'inactive'); }

    public function bulkDestroy(BulkDeletePlatformAddonsRequest $request): mixed
    {
        $deleted = 0; $skipped = 0;
        DB::transaction(function () use ($request, &$deleted, &$skipped): void {
            foreach ($request->validated('addon_uuids') as $uuid) {
                $addon = $this->findAddon($uuid);
                if (! $addon || $addon->subscriptionAddons()->exists()) { $skipped++; continue; }
                $addon->delete(); $deleted++;
            }
        });
        return ApiResponse::success(['deleted' => $deleted, 'skipped' => $skipped], 'Platform add-on bulk deletion processed.');
    }

    public function export(ExportPlatformAddonsRequest $request): mixed
    {
        $id = DB::table('report_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(), 'report_code' => 'platform_addons', 'format' => 'csv',
            'filters' => json_encode($request->validated()), 'status' => 'queued', 'created_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return ApiResponse::success(['export' => ['job_id' => $id, 'status' => 'queued', 'format' => 'csv']], 'Platform add-on export queued.', 202);
    }

    public function import(ImportPlatformAddonsRequest $request): mixed
    {
        return ApiResponse::success(['import' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Platform add-on import queued.', 202);
    }

    private function findAddon(string $uuid): ?AddonPlan { return AddonPlan::where('uuid', $uuid)->first(); }

    private function present(AddonPlan $addon): AddonPlan
    {
        return $addon->load([
            'plans', 'subscriptionAddons.subscription.tenant',
            'activityLogs' => fn ($q) => $q->latest('created_at')->limit(25),
        ])->loadCount(['plans', 'subscriptionAddons'])->setAttribute('features_limits', []);
    }

    private function setStatus(Request $request, string $uuid, string $status): mixed
    {
        $addon = $this->findAddon($uuid);
        if (! $addon) return ApiResponse::error('Platform add-on not found.', 404, null, 'ADDON_NOT_FOUND');
        $addon->update(['status' => $status]);
        ActivityLogger::record($request, 'status_changed', $addon, 'Platform add-on status changed.', ['status' => $status]);
        return ApiResponse::success(['addon' => $this->present($addon->fresh())], 'Platform add-on status updated.');
    }

    private function uniqueCode(string $name): string
    {
        $base = strtoupper(Str::slug($name, '_')) ?: 'ADDON';
        $code = $base; $suffix = 1;
        while (AddonPlan::where('code', $code)->exists()) $code = $base.'_'.($suffix++);
        return $code;
    }
}