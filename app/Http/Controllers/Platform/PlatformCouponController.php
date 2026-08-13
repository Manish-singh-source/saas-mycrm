<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformBillingCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformCouponController extends BasePlatformController
{
    public function __construct(private readonly PlatformBillingCatalogService $billing) {}

    public function index(Request $request)
    {
        $q = DB::table('coupons')->whereNull('deleted_at');
        foreach (['status', 'discount_type'] as $filter) if ($request->filled($filter)) $q->where($filter, $request->input($filter));
        if ($request->filled('search')) $q->where(fn($x) => $x->where('code', 'like', '%' . $request->string('search') . '%')->orWhere('name', 'like', '%' . $request->string('search') . '%'));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        return DB::transaction(function () use ($request, $data) {
            $id = DB::table('coupons')->insertGetId(['uuid' => (string) Str::uuid(), 'code' => Str::upper($data['code']), 'name' => $data['name'], 'discount_type' => $data['discount_type'], 'discount_value' => $data['discount_value'], 'starts_at' => $data['starts_at'] ?? null, 'expires_at' => $data['expires_at'] ?? null, 'max_redemptions' => $data['max_redemptions'] ?? null, 'status' => $data['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
            $this->syncPlans($id, $data['plan_uuids'] ?? null);
            $this->syncTenants($id, $data['tenant_uuids'] ?? null);
            $coupon = DB::table('coupons')->where('id', $id)->first();
            $this->billing->audit($request, 'coupon_created', 'coupons', $id, null, (array) $coupon);
            return $this->success(['coupon' => $coupon, ...$this->relations($id)], 'Coupon created.', 201);
        });
    }

    public function show(string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        return $this->success(['coupon' => $coupon, ...$this->relations($coupon->id)]);
    }

    public function update(Request $request, string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        $data = $this->data($request, $coupon->id, true);
        return DB::transaction(function () use ($request, $coupon, $data) {
            $update = collect($data)->except(['plan_uuids', 'tenant_uuids'])->all();
            if (isset($update['code'])) $update['code'] = Str::upper($update['code']);
            if ($update) DB::table('coupons')->where('id', $coupon->id)->update([...$update, 'updated_at' => now()]);
            if (array_key_exists('plan_uuids', $data)) $this->syncPlans($coupon->id, $data['plan_uuids']);
            if (array_key_exists('tenant_uuids', $data)) $this->syncTenants($coupon->id, $data['tenant_uuids']);
            $fresh = DB::table('coupons')->where('id', $coupon->id)->first();
            $this->billing->audit($request, 'coupon_updated', 'coupons', $coupon->id, (array) $coupon, (array) $fresh);
            return $this->success(['coupon' => $fresh, ...$this->relations($coupon->id)], 'Coupon updated.');
        });
    }

    public function destroy(Request $request, string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        $used = DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->exists();
        DB::table('coupons')->where('id', $coupon->id)->update(['status' => 'archived', 'deleted_at' => $used ? null : now(), 'updated_at' => now()]);
        $this->billing->audit($request, 'coupon_archived', 'coupons', $coupon->id, (array) $coupon, ['status' => 'archived']);
        return $this->success(null, $used ? 'Coupon archived.' : 'Coupon deleted.');
    }

    public function activate(Request $request, string $coupon_uuid)
    {
        return $this->status($request, $coupon_uuid, 'active');
    }
    public function deactivate(Request $request, string $coupon_uuid)
    {
        return $this->status($request, $coupon_uuid, 'inactive');
    }
    public function redemptions(Request $request, string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        $p = DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function plans(Request $request, string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        $data = $request->validate(['plan_uuids' => ['required', 'array'], 'plan_uuids.*' => ['uuid']]);
        $this->syncPlans($coupon->id, $data['plan_uuids']);
        return $this->success(['plans' => $this->relations($coupon->id)['plans']], 'Coupon plans updated.');
    }
    public function tenants(Request $request, string $coupon_uuid)
    {
        $coupon = $this->billing->byUuid('coupons', $coupon_uuid);
        $data = $request->validate(['tenant_uuids' => ['required', 'array'], 'tenant_uuids.*' => ['uuid']]);
        $this->syncTenants($coupon->id, $data['tenant_uuids']);
        return $this->success(['tenants' => $this->relations($coupon->id)['tenants']], 'Coupon tenants updated.');
    }
    public function export()
    {
        return $this->success(['export' => ['status' => 'queued', 'format' => 'csv']], 'Coupon export queued.');
    }

    private function status(Request $request, string $uuid, string $status)
    {
        $coupon = $this->billing->byUuid('coupons', $uuid);
        DB::table('coupons')->where('id', $coupon->id)->update(['status' => $status, 'updated_at' => now()]);
        $this->billing->audit($request, 'coupon_' . $status, 'coupons', $coupon->id, (array) $coupon, ['status' => $status]);
        return $this->success(['coupon' => DB::table('coupons')->where('id', $coupon->id)->first()], 'Coupon status updated.');
    }

    private function syncPlans(int $couponId, ?array $uuids): void
    {
        if ($uuids === null) return;
        DB::table('coupon_plan_assignments')->where('coupon_id', $couponId)->delete();
        foreach ($uuids as $uuid) {
            $plan = $this->billing->byUuid('plans', $uuid);
            DB::table('coupon_plan_assignments')->insert(['coupon_id' => $couponId, 'plan_id' => $plan->id]);
        }
    }

    private function syncTenants(int $couponId, ?array $uuids): void
    {
        if ($uuids === null) return;
        DB::table('coupon_tenant_assignments')->where('coupon_id', $couponId)->delete();
        foreach ($uuids as $uuid) DB::table('coupon_tenant_assignments')->insert(['coupon_id' => $couponId, 'tenant_id' => $this->billing->tenantId($uuid)]);
    }

    private function relations(int $couponId): array
    {
        return [
            'plans' => DB::table('coupon_plan_assignments')->join('plans', 'plans.id', '=', 'coupon_plan_assignments.plan_id')->where('coupon_id', $couponId)->select('plans.uuid', 'plans.name', 'plans.code')->get(),
            'tenants' => DB::table('coupon_tenant_assignments')->join('tenants', 'tenants.id', '=', 'coupon_tenant_assignments.tenant_id')->where('coupon_id', $couponId)->select('tenants.uuid', 'tenants.organization_name', 'tenants.slug')->get(),
            'redemptions' => DB::table('coupon_redemptions')->where('coupon_id', $couponId)->latest('id')->limit(25)->get(),
        ];
    }

    private function data(Request $request, ?int $id = null, bool $partial = false): array
    {
        return $request->validate([
            'code' => [$partial ? 'sometimes' : 'required', 'string', 'max:80', Rule::unique('coupons', 'code')->ignore($id)],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'discount_type' => [$partial ? 'sometimes' : 'required', Rule::in(['fixed', 'percent'])],
            'discount_value' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
            'plan_uuids' => ['nullable', 'array'],
            'plan_uuids.*' => ['uuid'],
            'tenant_uuids' => ['nullable', 'array'],
            'tenant_uuids.*' => ['uuid'],
        ]);
    }
}
