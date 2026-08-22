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
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 10));
        return $this->list($p->items(), $p, 'Coupons fetched successfully.', ['stats' => $this->stats()]);
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
        $p = $this->redemptionsQuery($coupon->id)->paginate((int) $request->integer('per_page', 10));
        return $this->list($p->items(), $p, 'Coupon redemptions fetched successfully.');
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

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'coupon_uuids' => ['required', 'array', 'min:1'],
            'coupon_uuids.*' => ['required', 'uuid'],
        ]);

        $coupons = DB::table('coupons')->whereIn('uuid', $data['coupon_uuids'])->whereNull('deleted_at')->get();
        $usedIds = DB::table('coupon_redemptions')->whereIn('coupon_id', $coupons->pluck('id'))->pluck('coupon_id')->all();
        $deleted = 0;
        $archived = 0;

        DB::transaction(function () use ($request, $coupons, $usedIds, &$deleted, &$archived): void {
            foreach ($coupons as $coupon) {
                $used = in_array($coupon->id, $usedIds, true);
                DB::table('coupons')->where('id', $coupon->id)->update([
                    'status' => 'archived',
                    'deleted_at' => $used ? null : now(),
                    'updated_at' => now(),
                ]);
                $this->billing->audit($request, $used ? 'coupon_archived' : 'coupon_deleted', 'coupons', $coupon->id, (array) $coupon, ['status' => 'archived']);
                $used ? $archived++ : $deleted++;
            }
        });

        return $this->success(['deleted' => $deleted, 'archived' => $archived], 'Coupon bulk delete completed.');
    }

    public function import()
    {
        return $this->success(['import' => ['status' => 'queued']], 'Coupon import queued.');
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
            'plans' => DB::table('coupon_plan_assignments')->join('plans', 'plans.id', '=', 'coupon_plan_assignments.plan_id')->where('coupon_id', $couponId)->select('plans.uuid', 'plans.name', 'plans.code', 'plans.status')->get(),
            'tenants' => DB::table('coupon_tenant_assignments')->join('tenants', 'tenants.id', '=', 'coupon_tenant_assignments.tenant_id')->where('coupon_id', $couponId)->select('tenants.uuid', 'tenants.organization_name', 'tenants.slug')->get(),
            'redemptions' => $this->redemptionsQuery($couponId)->limit(25)->get(),
            'payments' => DB::table('coupon_redemptions')->join('platform_invoices', 'platform_invoices.id', '=', 'coupon_redemptions.platform_invoice_id')->leftJoin('platform_payments', 'platform_payments.platform_invoice_id', '=', 'platform_invoices.id')->where('coupon_redemptions.coupon_id', $couponId)->select('platform_payments.uuid', 'platform_payments.payment_number', 'platform_payments.amount', 'platform_payments.currency', 'platform_payments.payment_status', 'platform_payments.paid_at', 'platform_invoices.invoice_number')->latest('platform_payments.id')->limit(25)->get(),
            'activity' => DB::table('activity_logs')->where('subject_type', 'coupons')->where('subject_id', $couponId)->latest('id')->limit(25)->get(),
        ];
    }

    private function redemptionsQuery(int $couponId)
    {
        return DB::table('coupon_redemptions')
            ->leftJoin('tenants', 'tenants.id', '=', 'coupon_redemptions.tenant_id')
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'coupon_redemptions.subscription_id')
            ->leftJoin('platform_invoices', 'platform_invoices.id', '=', 'coupon_redemptions.platform_invoice_id')
            ->where('coupon_redemptions.coupon_id', $couponId)
            ->select('coupon_redemptions.*', 'tenants.organization_name', 'tenants.slug', 'subscriptions.subscription_number', 'platform_invoices.invoice_number')
            ->latest('coupon_redemptions.id');
    }

    private function stats(): array
    {
        return [
            'total' => DB::table('coupons')->whereNull('deleted_at')->count(),
            'amount' => (float) DB::table('coupon_redemptions')->sum('discount_amount'),
            'active_success' => DB::table('coupons')->whereNull('deleted_at')->where('status', 'active')->count(),
            'failed_cancelled' => DB::table('coupons')->whereIn('status', ['failed', 'cancelled', 'canceled', 'archived', 'inactive'])->count(),
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
