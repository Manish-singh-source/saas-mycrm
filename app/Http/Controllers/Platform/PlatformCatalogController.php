<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformCatalogController extends BasePlatformController
{
    public function __construct(private readonly PlatformBillingCatalogService $billing) {}

    public function plans(Request $request)
    {
        $q = DB::table('plans')->whereNull('deleted_at');
        foreach (['status', 'billing_cycle', 'currency'] as $filter) {
            if ($request->filled($filter)) $q->where($filter, $request->input($filter));
        }
        if ($request->filled('search')) $q->where(fn($x) => $x->where('name', 'like', '%' . $request->string('search') . '%')->orWhere('code', 'like', '%' . $request->string('search') . '%'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));

        return $this->list($p->items(), $p);
    }

    public function storePlan(Request $request)
    {
        $data = $this->planData($request);
        $id = DB::table('plans')->insertGetId([...$data, 'uuid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $plan = DB::table('plans')->where('id', $id)->first();
        $this->billing->audit($request, 'plan_created', 'plans', $id, null, (array) $plan);

        return $this->success(['plan' => $plan], 'Plan created.', 201);
    }

    public function showPlan(string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        return $this->success(['plan' => $plan, ...$this->planRelations($plan)]);
    }

    public function updatePlan(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $data = $this->planData($request, $plan->id, true);
        DB::table('plans')->where('id', $plan->id)->update([...$data, 'updated_at' => now()]);
        $fresh = DB::table('plans')->where('id', $plan->id)->first();
        $this->billing->audit($request, 'plan_updated', 'plans', $plan->id, (array) $plan, (array) $fresh);

        return $this->success(['plan' => $fresh], 'Plan updated.');
    }

    public function archivePlan(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        DB::table('plans')->where('id', $plan->id)->update(['status' => 'archived', 'deleted_at' => now(), 'updated_at' => now()]);
        $this->billing->audit($request, 'plan_archived', 'plans', $plan->id, (array) $plan, ['status' => 'archived']);

        return $this->success(null, 'Plan archived.');
    }

    public function clonePlan(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:80', 'unique:plans,code'], 'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])]]);

        return DB::transaction(function () use ($request, $plan, $data) {
            $copy = (array) $plan;
            unset($copy['id'], $copy['uuid'], $copy['created_at'], $copy['updated_at'], $copy['deleted_at']);
            $id = DB::table('plans')->insertGetId([...$copy, ...$data, 'uuid' => (string) Str::uuid(), 'status' => $data['status'] ?? 'inactive', 'created_at' => now(), 'updated_at' => now()]);
            foreach (DB::table('plan_features')->where('plan_id', $plan->id)->get() as $feature) {
                DB::table('plan_features')->insert(['plan_id' => $id, 'feature_id' => $feature->feature_id, 'value' => $feature->value, 'metadata' => $feature->metadata, 'created_at' => now(), 'updated_at' => now()]);
            }
            $fresh = DB::table('plans')->where('id', $id)->first();
            $this->billing->audit($request, 'plan_cloned', 'plans', $id, (array) $plan, (array) $fresh);

            return $this->success(['plan' => $fresh], 'Plan cloned.', 201);
        });
    }

    public function planFeatures(string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        return $this->success(['features' => $this->planRelations($plan)['features']]);
    }

    public function replacePlanFeatures(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $data = $request->validate(['features' => ['required', 'array'], 'features.*.feature_uuid' => ['required', 'uuid'], 'features.*.value' => ['nullable'], 'features.*.metadata' => ['nullable', 'array']]);

        DB::transaction(function () use ($data, $plan): void {
            DB::table('plan_features')->where('plan_id', $plan->id)->delete();
            foreach ($data['features'] as $item) {
                $featureId = DB::table('features')->where('uuid', $item['feature_uuid'])->value('id');
                abort_if(! $featureId, 422, 'Invalid feature_uuid.');
                DB::table('plan_features')->insert(['plan_id' => $plan->id, 'feature_id' => $featureId, 'value' => isset($item['value']) ? (string) $item['value'] : null, 'metadata' => isset($item['metadata']) ? json_encode($item['metadata']) : null, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->billing->audit($request, 'plan_features_replaced', 'plans', $plan->id, null, $data);

        return $this->success(['features' => $this->planRelations($plan)['features']], 'Plan features updated.');
    }

    public function planSubscriptions(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $p = DB::table('subscriptions')->where('plan_id', $plan->id)->whereNull('deleted_at')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function features(Request $request)
    {
        $q = DB::table('features');
        if ($request->filled('module')) $q->where('module', $request->input('module'));
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        if ($request->filled('search')) $q->where(fn($x) => $x->where('name', 'like', '%' . $request->string('search') . '%')->orWhere('code', 'like', '%' . $request->string('search') . '%'));
        $p = $q->orderBy('module')->orderBy('name')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p, 'OK', ['grouped' => DB::table('features')->select('module')->distinct()->orderBy('module')->pluck('module')]);
    }

    public function storeFeature(Request $request)
    {
        $data = $this->featureData($request);
        $id = DB::table('features')->insertGetId([...$data, 'uuid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $feature = DB::table('features')->where('id', $id)->first();
        $this->billing->audit($request, 'feature_created', 'features', $id, null, (array) $feature);
        return $this->success(['feature' => $feature], 'Feature created.', 201);
    }

    public function showFeature(string $feature_uuid)
    {
        return $this->success(['feature' => $this->billing->byUuid('features', $feature_uuid)]);
    }

    public function updateFeature(Request $request, string $feature_uuid)
    {
        $feature = $this->billing->byUuid('features', $feature_uuid);
        $data = $this->featureData($request, $feature->id, true);
        DB::table('features')->where('id', $feature->id)->update([...$data, 'updated_at' => now()]);
        $fresh = DB::table('features')->where('id', $feature->id)->first();
        $this->billing->audit($request, 'feature_updated', 'features', $feature->id, (array) $feature, (array) $fresh);
        return $this->success(['feature' => $fresh], 'Feature updated.');
    }

    public function deleteFeature(Request $request, string $feature_uuid)
    {
        $feature = $this->billing->byUuid('features', $feature_uuid);
        DB::table('features')->where('id', $feature->id)->update(['status' => 'inactive', 'updated_at' => now()]);
        $this->billing->audit($request, 'feature_deactivated', 'features', $feature->id, (array) $feature, ['status' => 'inactive']);
        return $this->success(null, 'Feature deactivated.');
    }

    public function addons(Request $request)
    {
        $q = DB::table('addon_plans');
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function storeAddon(Request $request)
    {
        $data = $this->addonData($request);
        $id = DB::table('addon_plans')->insertGetId([...$data, 'uuid' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        $addon = DB::table('addon_plans')->where('id', $id)->first();
        $this->billing->audit($request, 'addon_plan_created', 'addon_plans', $id, null, (array) $addon);
        return $this->success(['addon' => $addon], 'Add-on plan created.', 201);
    }

    public function showAddon(string $addon_uuid)
    {
        return $this->success(['addon' => $this->billing->byUuid('addon_plans', $addon_uuid)]);
    }

    public function updateAddon(Request $request, string $addon_uuid)
    {
        $addon = $this->billing->byUuid('addon_plans', $addon_uuid);
        $data = $this->addonData($request, $addon->id, true);
        DB::table('addon_plans')->where('id', $addon->id)->update([...$data, 'updated_at' => now()]);
        $fresh = DB::table('addon_plans')->where('id', $addon->id)->first();
        $this->billing->audit($request, 'addon_plan_updated', 'addon_plans', $addon->id, (array) $addon, (array) $fresh);
        return $this->success(['addon' => $fresh], 'Add-on plan updated.');
    }

    public function archiveAddon(Request $request, string $addon_uuid)
    {
        $addon = $this->billing->byUuid('addon_plans', $addon_uuid);
        DB::table('addon_plans')->where('id', $addon->id)->update(['status' => 'inactive', 'updated_at' => now()]);
        $this->billing->audit($request, 'addon_plan_archived', 'addon_plans', $addon->id, (array) $addon, ['status' => 'inactive']);
        return $this->success(null, 'Add-on plan archived.');
    }

    public function exportPlans()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Plan export queued.');
    }

    private function planRelations(object $plan): array
    {
        return [
            'features' => DB::table('plan_features')->join('features', 'features.id', '=', 'plan_features.feature_id')->where('plan_features.plan_id', $plan->id)->select('features.*', 'plan_features.value', 'plan_features.metadata')->get(),
            'subscription_count' => DB::table('subscriptions')->where('plan_id', $plan->id)->whereNull('deleted_at')->count(),
        ];
    }

    private function planData(Request $request, ?int $id = null, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('plans', 'code')->ignore($id)],
            'description' => ['nullable', 'string'],
            'billing_cycle' => [$partial ? 'sometimes' : 'required', 'string', 'max:50'],
            'base_price' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_custom' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
        ]);
    }

    private function featureData(Request $request, ?int $id = null, bool $partial = false): array
    {
        return $request->validate([
            'module' => [$partial ? 'sometimes' : 'required', 'string', 'max:100'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('features', 'code')->ignore($id)],
            'data_type' => [$partial ? 'sometimes' : 'required', Rule::in(['boolean', 'integer', 'decimal', 'string', 'json'])],
            'unit' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }

    private function addonData(Request $request, ?int $id = null, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('addon_plans', 'code')->ignore($id)],
            'pricing_type' => [$partial ? 'sometimes' : 'required', 'string', 'max:50'],
            'price' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
    }
}
