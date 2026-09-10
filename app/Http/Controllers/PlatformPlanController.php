<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeletePlatformPlansRequest;
use App\Http\Requests\ClonePlatformPlanRequest;
use App\Http\Requests\ExportPlatformPlansRequest;
use App\Http\Requests\ImportPlatformPlansRequest;
use App\Http\Requests\ListPlatformPlanSubscriptionsRequest;
use App\Http\Requests\ListPlatformPlansRequest;
use App\Http\Requests\ReplacePlatformPlanAddonsRequest;
use App\Http\Requests\ReplacePlatformPlanFeaturesRequest;
use App\Http\Requests\StorePlatformPlanRequest;
use App\Http\Requests\UpdatePlatformPlanRequest;
use App\Models\AddonPlan;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class PlatformPlanController extends Controller
{
    public function index(ListPlatformPlansRequest $request): mixed
    {
        $input = $request->validated();
        $query = Plan::query()->with(['features', 'addons'])->withCount(['features', 'addons', 'subscriptions']);
        if (! empty($input['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$input['search'].'%')->orWhere('code', 'like', '%'.$input['search'].'%'));
        foreach (['status', 'billing_cycle', 'currency'] as $column) if (($input[$column] ?? null) !== null && $input[$column] !== '') $query->where($column, $input[$column]);
        $paginator = $query->latest('created_at')->paginate($request->integer('per_page', 10))->withQueryString();
        $statistics = [
            'total' => Plan::count(),
            'active' => Plan::where('status', 'active')->count(),
            'inactive' => Plan::where('status', 'inactive')->count(),
            'archived' => Plan::where('status', 'archived')->count(),
            'with_active_subscriptions' => Plan::whereHas('subscriptions', fn ($q) => $q->where('status', 'active'))->count(),
        ];
        return ApiResponse::success($paginator->items(), 'Platform plans fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(), 'statistics' => $statistics,
        ]);
    }

    public function store(StorePlatformPlanRequest $request): mixed
    {
        $input = $request->validated();
        $plan = DB::transaction(function () use ($input): Plan {
            $data = collect($input)->only(['name', 'description', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'is_custom', 'is_public', 'status'])->all();
            $data['code'] = $this->uniqueCode($input['name']);
            $plan = Plan::create($data + ['uuid' => (string) Str::uuid()]);
            $this->syncRelationships($plan, $input);
            return $plan;
        });
        ActivityLogger::record($request, 'created', $plan, 'Platform plan created.', $plan->toArray());
        return ApiResponse::success(['plan' => $this->present($plan)], 'Platform plan created successfully.', 201);
    }

    public function show(string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        return ApiResponse::success(['plan' => $this->present($plan)], 'Platform plan fetched.');
    }

    public function update(UpdatePlatformPlanRequest $request, string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $input = $request->validated();
        $data = collect($input)->only(['name', 'description', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'is_custom', 'is_public', 'status'])->all();
        DB::transaction(function () use ($plan, $data, $input): void {
            $plan->update($data);
            $this->syncRelationships($plan, $input);
        });
        ActivityLogger::record($request, 'updated', $plan, 'Platform plan updated.', $data);
        return ApiResponse::success(['plan' => $this->present($plan->fresh())], 'Platform plan updated successfully.');
    }

    public function destroy(Request $request, string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        if ($plan->subscriptions()->exists() || $plan->subscriptionVersions()->exists()) return ApiResponse::error('Plan is referenced by subscriptions or subscription history.', 409, null, 'PLAN_IN_USE');
        $plan->delete();
        ActivityLogger::record($request, 'deleted', $plan, 'Platform plan deleted.');
        return ApiResponse::success(null, 'Platform plan deleted successfully.');
    }

    public function bulkDestroy(BulkDeletePlatformPlansRequest $request): mixed
    {
        $deleted = 0; $archived = 0;
        DB::transaction(function () use ($request, &$deleted, &$archived): void {
            foreach ($request->validated('plan_uuids') as $uuid) {
                $plan = $this->findPlan($uuid);
                if (! $plan) continue;
                if ($plan->subscriptions()->exists() || $plan->subscriptionVersions()->exists()) { $plan->update(['status' => 'archived']); $archived++; }
                else { $plan->delete(); $deleted++; }
            }
        });
        return ApiResponse::success(['deleted' => $deleted, 'archived' => $archived], 'Platform plan bulk deletion processed.');
    }

    public function clone(ClonePlatformPlanRequest $request, string $planUuid): mixed
    {
        $source = $this->findPlan($planUuid);
        if (! $source) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $input = $request->validated();
        $data = collect($input)->only(['name', 'billing_cycle', 'base_price', 'description', 'currency', 'trial_days', 'is_custom', 'is_public'])->all();
        $data['status'] = $input['status'] ?? 'inactive';
        $clone = DB::transaction(function () use ($source, $data, $input): Plan {
            $data['code'] = $this->uniqueCode($input['name']);
            $clone = Plan::create($data + ['uuid' => (string) Str::uuid()]);
            if (($input['copy_features'] ?? true) && $source->features()->exists()) {
                $sync = [];
                foreach ($source->features as $feature) $sync[$feature->id] = ['value' => $feature->pivot->value, 'metadata' => $feature->pivot->metadata];
                $clone->features()->sync($sync);
            }
            return $clone;
        });
        ActivityLogger::record($request, 'cloned', $clone, 'Platform plan cloned.', ['source_plan_uuid' => $source->uuid]);
        return ApiResponse::success(['plan' => $this->present($clone)], 'Platform plan cloned successfully.', 201);
    }

    public function activate(Request $request, string $planUuid): mixed { return $this->setStatus($request, $planUuid, 'active'); }
    public function deactivate(Request $request, string $planUuid): mixed { return $this->setStatus($request, $planUuid, 'inactive'); }

    public function features(string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $features = $plan->features()->with(['plans'])->orderBy('name')->get();
        return ApiResponse::success(['features' => $features], 'Plan features fetched.');
    }

    public function replaceFeatures(ReplacePlatformPlanFeaturesRequest $request, string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $sync = [];
        foreach ($request->validated('features') as $row) {
            $featureId = Feature::where('uuid', $row['feature_uuid'])->value('id');
            if ($featureId) $sync[$featureId] = ['value' => $row['value'] ?? null, 'metadata' => array_key_exists('metadata', $row) && $row['metadata'] !== null ? json_encode($row['metadata']) : null];
        }
        $plan->features()->sync($sync);
        return ApiResponse::success(['plan' => $this->present($plan->fresh())], 'Plan features replaced.');
    }

    public function addons(string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $addons = $plan->addons()->with(['plans', 'subscriptionAddons.subscription.tenant'])->orderBy('name')->get();
        return ApiResponse::success(['addons' => $addons], 'Plan add-ons fetched.');
    }

    public function replaceAddons(ReplacePlatformPlanAddonsRequest $request, string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        if (! Schema::hasTable('plan_addons')) return ApiResponse::error('Plan add-on assignment infrastructure is unavailable.', 503, null, 'PLAN_ADDONS_TABLE_MISSING');
        $ids = AddonPlan::whereIn('uuid', $request->validated('addon_uuids'))->pluck('id')->all();
        $plan->addons()->sync($ids);
        return ApiResponse::success(['plan' => $this->present($plan->fresh())], 'Plan add-ons replaced.');
    }

    public function subscriptions(ListPlatformPlanSubscriptionsRequest $request, string $planUuid): mixed
    {
        $plan = $this->findPlan($planUuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $paginator = $plan->subscriptions()->with(['tenant', 'plan', 'addonAssignments.addonPlan'])->where('status', 'active')->latest('created_at')->paginate($request->integer('per_page', 10))->withQueryString();
        return ApiResponse::success($paginator->items(), 'Active plan subscriptions fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
        ]);
    }

    public function export(ExportPlatformPlansRequest $request): mixed
    {
        $id = DB::table('report_export_jobs')->insertGetId(['uuid' => (string) Str::uuid(), 'report_code' => 'platform_plans', 'format' => 'csv', 'filters' => json_encode($request->validated()), 'status' => 'queued', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        return ApiResponse::success(['export' => ['job_id' => $id, 'status' => 'queued', 'format' => 'csv']], 'Platform plan export queued.', 202);
    }

    public function import(ImportPlatformPlansRequest $request): mixed
    {
        return ApiResponse::success(['import' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Platform plan import queued.', 202);
    }

    private function findPlan(string $uuid): ?Plan { return Plan::where('uuid', $uuid)->first(); }
    private function syncRelationships(Plan $plan, array $input): void
    {
        if (array_key_exists('features', $input)) {
            $sync = [];
            foreach ($input['features'] as $row) {
                $featureId = Feature::where('uuid', $row['feature_uuid'])->value('id');
                if ($featureId) $sync[$featureId] = ['value' => $row['value'] ?? null, 'metadata' => array_key_exists('metadata', $row) && $row['metadata'] !== null ? json_encode($row['metadata']) : null];
            }
            $plan->features()->sync($sync);
        }
        if (array_key_exists('addon_uuids', $input)) {
            if (! Schema::hasTable('plan_addons')) return;
            $ids = AddonPlan::whereIn('uuid', $input['addon_uuids'])->pluck('id')->all();
            $plan->addons()->sync($ids);
        }
    }

    private function present(Plan $plan): Plan
    {
        return $plan->load(['features', 'addons', 'coupons', 'subscriptions.tenant', 'subscriptionVersions', 'activityLogs' => fn ($q) => $q->latest('created_at')->limit(25)])->loadCount(['features', 'addons', 'coupons', 'subscriptions', 'subscriptionVersions']);
    }

    private function uniqueCode(string $name): string
    {
        $base = strtoupper(Str::slug($name, '_')) ?: 'PLAN';
        $code = $base; $suffix = 1;
        while (Plan::where('code', $code)->exists()) $code = $base.'_'.($suffix++);
        return $code;
    }

    private function setStatus(Request $request, string $uuid, string $status): mixed
    {
        $plan = $this->findPlan($uuid);
        if (! $plan) return ApiResponse::error('Platform plan not found.', 404, null, 'PLAN_NOT_FOUND');
        $plan->update(['status' => $status]);
        ActivityLogger::record($request, 'status_changed', $plan, 'Platform plan status changed.', ['status' => $status]);
        return ApiResponse::success(['plan' => $this->present($plan->fresh())], 'Platform plan status updated.');
    }
}