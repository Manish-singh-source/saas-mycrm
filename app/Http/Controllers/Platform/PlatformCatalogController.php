<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 10));

        return $this->list($p->items(), $p, 'Plans fetched successfully.', ['stats' => $this->planStats()]);
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

        return $this->success(['plan' => $fresh, ...$this->planRelations($fresh)], 'Plan updated.');
    }

    public function deletePlan(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $assigned = DB::table('subscriptions')->where('plan_id', $plan->id)->exists()
            || DB::table('subscription_versions')->where('plan_id', $plan->id)->exists();

        if ($assigned) {
            return ApiResponse::businessError(
                'This plan is assigned to one or more subscriptions and cannot be deleted.',
                'PLAN_IN_USE',
                409
            );
        }

        DB::table('plans')->where('id', $plan->id)->delete();
        $this->billing->audit($request, 'plan_deleted', 'plans', $plan->id, (array) $plan, null);

        return $this->success(null, 'Plan deleted.');
    }

    public function clonePlan(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'unique:plans,code'],
            'description' => ['nullable', 'string'],
            'billing_cycle' => ['required', 'string', 'max:50'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_custom' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
            'copy_features' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $plan, $data) {
            $copy = (array) $plan;
            unset($copy['id'], $copy['uuid'], $copy['created_at'], $copy['updated_at'], $copy['deleted_at']);
            $copyFeatures = (bool) ($data['copy_features'] ?? true);
            unset($data['copy_features']);

            $id = DB::table('plans')->insertGetId([...$copy, ...$data, 'uuid' => (string) Str::uuid(), 'status' => $data['status'] ?? 'inactive', 'created_at' => now(), 'updated_at' => now()]);

            if ($copyFeatures) {
                foreach (DB::table('plan_features')->where('plan_id', $plan->id)->get() as $feature) {
                    DB::table('plan_features')->insert(['plan_id' => $id, 'feature_id' => $feature->feature_id, 'value' => $feature->value, 'metadata' => $feature->metadata, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            $fresh = DB::table('plans')->where('id', $id)->first();
            $this->billing->audit($request, 'plan_cloned', 'plans', $id, (array) $plan, ['plan' => (array) $fresh, 'copy_features' => $copyFeatures]);

            return $this->success(['plan' => $fresh], 'Plan cloned.', 201);
        });
    }

    public function activatePlan(Request $request, string $plan_uuid)
    {
        return $this->setPlanStatus($request, $plan_uuid, 'active');
    }

    public function deactivatePlan(Request $request, string $plan_uuid)
    {
        return $this->setPlanStatus($request, $plan_uuid, 'inactive');
    }

    public function bulkDeletePlans(Request $request)
    {
        $data = $request->validate([
            'plan_uuids' => ['required', 'array', 'min:1'],
            'plan_uuids.*' => ['required', 'uuid'],
        ]);

        $plans = DB::table('plans')->whereIn('uuid', $data['plan_uuids'])->whereNull('deleted_at')->get();
        $blockedSubscriptionIds = DB::table('subscriptions')->whereIn('plan_id', $plans->pluck('id'))->pluck('plan_id')->all();
        $blockedVersionIds = DB::table('subscription_versions')->whereIn('plan_id', $plans->pluck('id'))->pluck('plan_id')->all();
        $blocked = array_values(array_unique([...$blockedSubscriptionIds, ...$blockedVersionIds]));
        $deleted = 0;
        $archived = 0;

        DB::transaction(function () use ($request, $plans, $blocked, &$deleted, &$archived): void {
            foreach ($plans as $plan) {
                if (in_array($plan->id, $blocked, true)) {
                    DB::table('plans')->where('id', $plan->id)->update(['status' => 'archived', 'updated_at' => now()]);
                    $this->billing->audit($request, 'plan_archived', 'plans', $plan->id, (array) $plan, ['status' => 'archived']);
                    $archived++;
                    continue;
                }

                DB::table('plans')->where('id', $plan->id)->delete();
                $this->billing->audit($request, 'plan_deleted', 'plans', $plan->id, (array) $plan, null);
                $deleted++;
            }
        });

        return $this->success(['deleted' => $deleted, 'archived' => $archived], 'Plan bulk delete completed.');
    }

    public function importPlans()
    {
        return $this->success(['import' => ['status' => 'queued']], 'Plan import queued.');
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

    public function planAddons(string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        return $this->success(['addons' => $this->planAddonsFor($plan)], 'Plan add-ons fetched successfully.');
    }

    public function replacePlanAddons(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $data = $request->validate([
            'addon_uuids' => ['required', 'array'],
            'addon_uuids.*' => ['required', 'uuid'],
        ]);

        if (! Schema::hasTable('plan_addons')) {
            return ApiResponse::businessError(
                'Plan add-on assignments are not available until migrations are run.',
                'PLAN_ADDONS_TABLE_MISSING',
                503
            );
        }

        DB::transaction(function () use ($data, $plan): void {
            DB::table('plan_addons')->where('plan_id', $plan->id)->delete();
            foreach ($data['addon_uuids'] as $uuid) {
                $addonId = DB::table('addon_plans')->where('uuid', $uuid)->value('id');
                abort_if(! $addonId, 422, 'Invalid addon_uuid.');
                DB::table('plan_addons')->insert(['plan_id' => $plan->id, 'addon_plan_id' => $addonId, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        $this->billing->audit($request, 'plan_addons_replaced', 'plans', $plan->id, null, $data);
        return $this->success(['addons' => $this->planAddonsFor($plan)], 'Plan add-ons updated.');
    }

    public function planSubscriptions(Request $request, string $plan_uuid)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        $p = DB::table('subscriptions')->leftJoin('tenants', 'tenants.id', '=', 'subscriptions.tenant_id')->where('subscriptions.plan_id', $plan->id)->whereNull('subscriptions.deleted_at')->where('subscriptions.status', 'active')->select('subscriptions.*', 'tenants.organization_name as tenant_name')->latest('subscriptions.id')->paginate((int) $request->integer('per_page', 10));
        return $this->list($p->items(), $p, 'Plan subscriptions fetched successfully.');
    }

    public function features(Request $request)
    {
        $q = DB::table('features');
        if ($request->filled('module')) $q->where('module', $request->input('module'));
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        if ($request->filled('search')) $q->where(fn($x) => $x->where('name', 'like', '%' . $request->string('search') . '%')->orWhere('code', 'like', '%' . $request->string('search') . '%'));
        $p = $q->orderBy('module')->orderBy('name')->paginate((int) $request->integer('per_page', 10));
        return $this->list($p->items(), $p, 'Features fetched successfully.', ['grouped' => DB::table('features')->select('module')->distinct()->orderBy('module')->pluck('module'), 'stats' => $this->featureStats()]);
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
        $feature = $this->billing->byUuid('features', $feature_uuid);

        return $this->success([
            'feature' => $feature,
            ...$this->featureRelations($feature),
        ]);
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
        $assigned = DB::table('plan_features')->where('feature_id', $feature->id)->exists()
            || DB::table('subscription_usage')->where('feature_id', $feature->id)->exists();

        if ($assigned) {
            return ApiResponse::businessError(
                'This feature is assigned to one or more plans or usage records and cannot be deleted.',
                'FEATURE_IN_USE',
                409
            );
        }

        DB::table('features')->where('id', $feature->id)->delete();
        $this->billing->audit($request, 'feature_deleted', 'features', $feature->id, (array) $feature, null);

        return $this->success(null, 'Feature deleted.');
    }

    public function bulkDeleteFeatures(Request $request)
    {
        $data = $request->validate([
            'feature_uuids' => ['required', 'array', 'min:1'],
            'feature_uuids.*' => ['required', 'uuid'],
        ]);

        $features = DB::table('features')->whereIn('uuid', $data['feature_uuids'])->get();
        $blockedPlanFeatureIds = DB::table('plan_features')->whereIn('feature_id', $features->pluck('id'))->pluck('feature_id')->all();
        $blockedUsageFeatureIds = DB::table('subscription_usage')->whereIn('feature_id', $features->pluck('id'))->pluck('feature_id')->all();
        $blocked = array_values(array_unique([...$blockedPlanFeatureIds, ...$blockedUsageFeatureIds]));
        $deleted = 0;

        DB::transaction(function () use ($request, $features, $blocked, &$deleted): void {
            foreach ($features as $feature) {
                if (in_array($feature->id, $blocked, true)) {
                    continue;
                }

                DB::table('features')->where('id', $feature->id)->delete();
                $this->billing->audit($request, 'feature_deleted', 'features', $feature->id, (array) $feature, null);
                $deleted++;
            }
        });

        return $this->success([
            'deleted' => $deleted,
            'skipped' => count($blocked),
        ], 'Feature bulk delete completed.');
    }

    public function exportFeatures()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Feature export queued.');
    }

    public function importFeatures()
    {
        return $this->success(['import' => ['status' => 'queued']], 'Feature import queued.');
    }
    public function addons(Request $request)
    {
        $q = DB::table('addon_plans');
        if ($request->filled('search')) $q->where(fn($x) => $x->where('name', 'like', '%' . $request->string('search') . '%')->orWhere('code', 'like', '%' . $request->string('search') . '%'));
        if ($request->filled('status')) $q->where('status', $request->input('status'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 10));
        return $this->list($p->items(), $p, 'Add-ons fetched successfully.', ['stats' => $this->addonStats()]);
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
        $addon = $this->billing->byUuid('addon_plans', $addon_uuid);

        return $this->success([
            'addon' => $addon,
            'features_limits' => [],
            'subscriptions' => $this->addonSubscriptions($addon),
            'activity' => DB::table('activity_logs')
                ->where('subject_type', 'addon_plans')
                ->where('subject_id', $addon->id)
                ->latest('id')
                ->limit(25)
                ->get(),
        ]);
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

    public function deleteAddon(Request $request, string $addon_uuid)
    {
        $addon = $this->billing->byUuid('addon_plans', $addon_uuid);
        $assigned = DB::table('subscription_addons')->where('addon_plan_id', $addon->id)->exists();

        if ($assigned) {
            return ApiResponse::businessError(
                'This add-on plan is assigned to one or more subscriptions and cannot be deleted.',
                'ADDON_PLAN_IN_USE',
                409
            );
        }

        DB::table('addon_plans')->where('id', $addon->id)->delete();
        $this->billing->audit($request, 'addon_plan_deleted', 'addon_plans', $addon->id, (array) $addon, null);

        return $this->success(null, 'Add-on plan deleted.');
    }

    public function bulkDeleteAddons(Request $request)
    {
        $data = $request->validate([
            'addon_uuids' => ['required', 'array', 'min:1'],
            'addon_uuids.*' => ['required', 'uuid'],
        ]);

        $addons = DB::table('addon_plans')->whereIn('uuid', $data['addon_uuids'])->get();
        $blocked = DB::table('subscription_addons')->whereIn('addon_plan_id', $addons->pluck('id'))->pluck('addon_plan_id')->all();
        $deleted = 0;

        DB::transaction(function () use ($request, $addons, $blocked, &$deleted): void {
            foreach ($addons as $addon) {
                if (in_array($addon->id, $blocked, true)) {
                    continue;
                }

                DB::table('addon_plans')->where('id', $addon->id)->delete();
                $this->billing->audit($request, 'addon_plan_deleted', 'addon_plans', $addon->id, (array) $addon, null);
                $deleted++;
            }
        });

        return $this->success([
            'deleted' => $deleted,
            'skipped' => count($blocked),
        ], 'Add-on bulk delete completed.');
    }

    public function exportAddons()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Add-on export queued.');
    }

    public function importAddons()
    {
        return $this->success(['import' => ['status' => 'queued']], 'Add-on import queued.');
    }

    public function exportPlans()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Plan export queued.');
    }

    private function setPlanStatus(Request $request, string $plan_uuid, string $status)
    {
        $plan = $this->billing->byUuid('plans', $plan_uuid);
        DB::table('plans')->where('id', $plan->id)->update(['status' => $status, 'updated_at' => now()]);
        $fresh = DB::table('plans')->where('id', $plan->id)->first();
        $this->billing->audit($request, 'plan_' . $status, 'plans', $plan->id, (array) $plan, ['status' => $status]);

        return $this->success(['plan' => $fresh], 'Plan status updated.');
    }

    private function planStats(): array
    {
        $base = DB::table('plans')->whereNull('deleted_at');

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'archived' => (clone $base)->where('status', 'archived')->count(),
            'catalog_value' => (float) (clone $base)->sum('base_price'),
            'public' => (clone $base)->where('is_public', true)->count(),
        ];
    }

    private function featureStats(): array
    {
        return [
            'total' => DB::table('features')->count(),
            'active' => DB::table('features')->where('status', 'active')->count(),
            'archived' => DB::table('features')->where('status', 'archived')->count(),
            'catalog_value' => 0,
            'public' => 0,
        ];
    }

    private function addonStats(): array
    {
        return [
            'total' => DB::table('addon_plans')->count(),
            'active' => DB::table('addon_plans')->where('status', 'active')->count(),
            'archived' => DB::table('addon_plans')->where('status', 'archived')->count(),
            'catalog_value' => (float) DB::table('addon_plans')->sum('price'),
            'public' => DB::table('addon_plans')->where('is_public', true)->count(),
        ];
    }
    private function featureRelations(object $feature): array
    {
        return [
            'features_limits' => DB::table('plan_features')
                ->join('plans', 'plans.id', '=', 'plan_features.plan_id')
                ->where('plan_features.feature_id', $feature->id)
                ->select('plans.uuid as plan_uuid', 'plans.name as plan_name', 'plans.code as plan_code', 'plans.status as plan_status', 'plan_features.value', 'plan_features.metadata')
                ->get(),
            'subscriptions' => DB::table('subscription_usage')
                ->join('subscriptions', 'subscriptions.id', '=', 'subscription_usage.subscription_id')
                ->leftJoin('tenants', 'tenants.id', '=', 'subscriptions.tenant_id')
                ->where('subscription_usage.feature_id', $feature->id)
                ->where('subscriptions.status', 'active')
                ->whereNull('subscriptions.deleted_at')
                ->select('subscriptions.uuid', 'subscriptions.subscription_number', 'subscriptions.status', 'tenants.organization_name', 'subscription_usage.used_value', 'subscription_usage.limit_value', 'subscription_usage.period_start', 'subscription_usage.period_end')
                ->latest('subscription_usage.id')
                ->limit(25)
                ->get(),
            'activity' => DB::table('activity_logs')
                ->where('subject_type', 'features')
                ->where('subject_id', $feature->id)
                ->latest('id')
                ->limit(25)
                ->get(),
        ];
    }
    private function planRelations(object $plan): array
    {
        return [
            'features' => $this->planFeaturesFor($plan),
            'addons' => $this->planAddonsFor($plan),
            'coupon_history' => $this->planCouponHistoryFor($plan),
            'subscriptions' => $this->planActiveSubscriptionsFor($plan),
            'activity' => Schema::hasTable('activity_logs') ? DB::table('activity_logs')->where('subject_type', 'plans')->where('subject_id', $plan->id)->latest('id')->limit(25)->get() : collect(),
            'subscription_count' => Schema::hasTable('subscriptions') ? DB::table('subscriptions')->where('plan_id', $plan->id)->whereNull('deleted_at')->count() : 0,
        ];
    }

    private function planFeaturesFor(object $plan): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('plan_features') || ! Schema::hasTable('features')) {
            return collect();
        }

        return DB::table('plan_features')
            ->join('features', 'features.id', '=', 'plan_features.feature_id')
            ->where('plan_features.plan_id', $plan->id)
            ->select('features.*', 'plan_features.value', 'plan_features.metadata')
            ->get();
    }

    private function planAddonsFor(object $plan): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('plan_addons') || ! Schema::hasTable('addon_plans')) {
            return collect();
        }

        return DB::table('plan_addons')
            ->join('addon_plans', 'addon_plans.id', '=', 'plan_addons.addon_plan_id')
            ->where('plan_addons.plan_id', $plan->id)
            ->select('addon_plans.*')
            ->get();
    }

    private function planCouponHistoryFor(object $plan): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('coupon_plan_assignments') || ! Schema::hasTable('coupons')) {
            return collect();
        }

        $hasRedemptions = Schema::hasTable('coupon_redemptions');
        $hasTenants = Schema::hasTable('tenants');
        $query = DB::table('coupon_plan_assignments')
            ->join('coupons', 'coupons.id', '=', 'coupon_plan_assignments.coupon_id')
            ->where('coupon_plan_assignments.plan_id', $plan->id);

        if ($hasRedemptions) {
            $query->leftJoin('coupon_redemptions', 'coupon_redemptions.coupon_id', '=', 'coupons.id');
        }

        if ($hasTenants && $hasRedemptions) {
            $query->leftJoin('tenants', 'tenants.id', '=', 'coupon_redemptions.tenant_id');
        }

        return $query->select(
            'coupons.uuid',
            'coupons.code',
            'coupons.name',
            'coupons.status',
            $hasRedemptions ? 'coupon_redemptions.discount_amount' : DB::raw('NULL as discount_amount'),
            $hasRedemptions ? 'coupon_redemptions.redeemed_at' : DB::raw('NULL as redeemed_at'),
            $hasTenants && $hasRedemptions ? 'tenants.organization_name as tenant_name' : DB::raw('NULL as tenant_name')
        )
            ->latest($hasRedemptions ? 'coupon_redemptions.id' : 'coupon_plan_assignments.id')
            ->limit(25)
            ->get();
    }

    private function planActiveSubscriptionsFor(object $plan): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('subscriptions')) {
            return collect();
        }

        $hasTenants = Schema::hasTable('tenants');
        $query = DB::table('subscriptions')
            ->where('subscriptions.plan_id', $plan->id)
            ->where('subscriptions.status', 'active')
            ->whereNull('subscriptions.deleted_at');

        if ($hasTenants) {
            $query->leftJoin('tenants', 'tenants.id', '=', 'subscriptions.tenant_id');
        }

        return $query
            ->select(
                'subscriptions.*',
                $hasTenants ? 'tenants.organization_name as tenant_name' : DB::raw('NULL as tenant_name')
            )
            ->latest('subscriptions.id')
            ->limit(25)
            ->get();
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
        $data = $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80', Rule::unique('addon_plans', 'code')->ignore($id)],
            'pricing_type' => [$partial ? 'sometimes' : 'required', Rule::in(['recurring', 'one time', 'usage based', 'tiered'])],
            'price' => [$partial ? 'sometimes' : 'required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
        ]);

        if (! $partial && empty($data['code'])) {
            $data['code'] = $this->nextAddonCode();
        }

        return $data;
    }

    private function nextAddonCode(): string
    {
        $lastCode = DB::table('addon_plans')
            ->where('code', 'like', 'ADD-ON-%')
            ->orderByDesc('id')
            ->value('code');

        $number = $lastCode && preg_match('/ADD-ON-(\d+)$/', $lastCode, $matches)
            ? ((int) $matches[1]) + 1
            : 1;

        return 'ADD-ON-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    private function addonSubscriptions(object $addon): \Illuminate\Support\Collection
    {
        return DB::table('subscription_addons')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_addons.subscription_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'subscriptions.tenant_id')
            ->where('subscription_addons.addon_plan_id', $addon->id)
            ->where('subscription_addons.status', 'active')
            ->whereNull('subscriptions.deleted_at')
            ->select(
                'subscription_addons.*',
                'subscriptions.uuid as subscription_uuid',
                'subscriptions.subscription_number',
                'subscriptions.status as subscription_status',
                'subscriptions.currency',
                'tenants.uuid as tenant_uuid',
                'tenants.organization_name as tenant_name'
            )
            ->latest('subscription_addons.id')
            ->get();
    }
}




