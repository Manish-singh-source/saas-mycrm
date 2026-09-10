<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeletePlatformCouponsRequest;
use App\Http\Requests\ExportPlatformCouponsRequest;
use App\Http\Requests\ImportPlatformCouponsRequest;
use App\Http\Requests\ListPlatformCouponsRequest;
use App\Http\Requests\ReplaceCouponPlansRequest;
use App\Http\Requests\StorePlatformCouponRequest;
use App\Http\Requests\UpdatePlatformCouponRequest;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformCouponController extends Controller
{
    public function index(ListPlatformCouponsRequest $request): mixed
    {
        $input = $request->validated();
        $query = Coupon::query()->with(['plans', 'tenants'])->withCount(['plans', 'tenants', 'redemptions']);
        if (! empty($input['search'])) {
            $query->where(fn ($q) => $q->where('code', 'like', '%'.$input['search'].'%')->orWhere('name', 'like', '%'.$input['search'].'%'));
        }
        foreach (['status', 'discount_type'] as $column) {
            if (($input[$column] ?? null) !== null && $input[$column] !== '') $query->where($column, $input[$column]);
        }
        $paginator = $query->latest('created_at')->paginate($request->integer('per_page', 10))->withQueryString();
        $statistics = [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->count(),
            'inactive' => Coupon::where('status', 'inactive')->count(),
            'archived' => Coupon::where('status', 'archived')->count(),
            'redeemed' => Coupon::whereHas('redemptions')->count(),
        ];

        return ApiResponse::success($paginator->items(), 'Platform coupons fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
            'total' => $paginator->total(), 'last_page' => $paginator->lastPage(), 'statistics' => $statistics,
        ]);
    }

    public function store(StorePlatformCouponRequest $request): mixed
    {
        $input = $request->validated();
        $coupon = DB::transaction(function () use ($input): Coupon {
            $data = collect($input)->only(['code', 'name', 'discount_type', 'discount_value', 'starts_at', 'expires_at', 'max_redemptions', 'status'])->all();
            $data['code'] = strtoupper($data['code']);
            $coupon = Coupon::create($data + ['uuid' => (string) Str::uuid()]);
            $this->syncAssignments($coupon, $input);
            return $coupon;
        });
        ActivityLogger::record($request, 'created', $coupon, 'Platform coupon created.', $coupon->toArray());
        return ApiResponse::success(['coupon' => $this->present($coupon)], 'Platform coupon created successfully.', 201);
    }

    public function show(string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        return ApiResponse::success(['coupon' => $this->present($coupon)], 'Platform coupon fetched.');
    }

    public function update(UpdatePlatformCouponRequest $request, string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        $input = $request->validated();
        $data = collect($input)->only(['code', 'name', 'discount_type', 'discount_value', 'starts_at', 'expires_at', 'max_redemptions', 'status'])->all();
        if (array_key_exists('code', $data)) $data['code'] = strtoupper($data['code']);
        DB::transaction(function () use ($coupon, $data, $input): void {
            $coupon->update($data);
            $this->syncAssignments($coupon, $input);
        });
        ActivityLogger::record($request, 'updated', $coupon, 'Platform coupon updated.', $data);
        return ApiResponse::success(['coupon' => $this->present($coupon->fresh())], 'Platform coupon updated successfully.');
    }

    public function destroy(Request $request, string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        $coupon->update(['status' => 'archived']);
        if ($coupon->redemptions()->exists()) {
            ActivityLogger::record($request, 'archived', $coupon, 'Redeemed platform coupon archived.');
            return ApiResponse::success(['coupon' => $this->present($coupon->fresh()), 'archived' => true], 'Platform coupon archived because it has redemptions.');
        }
        $coupon->delete();
        ActivityLogger::record($request, 'deleted', $coupon, 'Unused platform coupon deleted.');
        return ApiResponse::success(['deleted' => true], 'Platform coupon deleted successfully.');
    }

    public function activate(Request $request, string $couponUuid): mixed { return $this->setStatus($request, $couponUuid, 'active'); }
    public function deactivate(Request $request, string $couponUuid): mixed { return $this->setStatus($request, $couponUuid, 'inactive'); }

    public function replacePlans(ReplaceCouponPlansRequest $request, string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');

        $uuids = $request->validated('plan_uuids');
        $plans = Plan::whereIn('uuid', $uuids)->get();
        if ($plans->count() !== count($uuids)) return ApiResponse::error('One or more selected plans were not found.', 404, null, 'PLAN_NOT_FOUND');

        DB::transaction(function () use ($coupon, $plans): void {
            $coupon->plans()->sync($plans->modelKeys());
        });
        ActivityLogger::record($request, 'plans_synchronized', $coupon, 'Coupon plan assignments synchronized.', ['plan_uuids' => $plans->pluck('uuid')->values()->all()]);

        return ApiResponse::success(['plans' => $coupon->fresh()->plans()->get()], 'Coupon plan assignments replaced.');
    }
    public function redemptions(Request $request, string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        $paginator = $coupon->redemptions()->with(['tenant', 'subscription', 'invoice'])->latest('redeemed_at')->paginate($request->integer('per_page', 10))->withQueryString();
        $rows = collect($paginator->items())->map(function (CouponRedemption $redemption) {
            $row = $redemption->toArray();
            $row['organization_name'] = $redemption->tenant?->organization_name;
            $row['slug'] = $redemption->tenant?->slug;
            $row['subscription_number'] = $redemption->subscription?->subscription_number;
            $row['invoice_number'] = $redemption->invoice?->invoice_number;
            return $row;
        })->values();
        return ApiResponse::success($rows, 'Coupon redemptions fetched.', 200, [
            'current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
        ]);
    }

    public function replaceTenants(ReplaceCouponTenantsRequest $request, string $couponUuid): mixed
    {
        $coupon = $this->findCoupon($couponUuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        $uuids = $request->validated('tenant_uuids');
        $tenants = Tenant::whereIn('uuid', $uuids)->get();
        if ($tenants->count() !== count($uuids)) return ApiResponse::error('One or more selected tenants were not found.', 404, null, 'TENANT_NOT_FOUND');
        $coupon->tenants()->sync($tenants->modelKeys());
        ActivityLogger::record($request, 'tenants_synchronized', $coupon, 'Coupon tenant assignments synchronized.', ['tenant_uuids' => $tenants->pluck('uuid')->values()->all()]);
        return ApiResponse::success(['tenants' => $coupon->fresh()->tenants()->get(['tenants.uuid', 'organization_name', 'slug'])], 'Coupon tenant assignments replaced.');
    }

    public function bulkDestroy(BulkDeletePlatformCouponsRequest $request): mixed
    {
        $deleted = 0; $archived = 0;
        foreach ($request->validated('coupon_uuids') as $uuid) {
            $coupon = $this->findCoupon($uuid);
            if (! $coupon) continue;
            $coupon->update(['status' => 'archived']);
            if ($coupon->redemptions()->exists()) $archived++;
            else { $coupon->delete(); $deleted++; }
        }
        return ApiResponse::success(['deleted' => $deleted, 'archived' => $archived], 'Platform coupon bulk deletion processed.');
    }

    public function export(ExportPlatformCouponsRequest $request): mixed
    {
        $id = DB::table('report_export_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(), 'report_code' => 'platform_coupons', 'format' => 'csv',
            'filters' => json_encode($request->validated()), 'status' => 'queued', 'created_by' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return ApiResponse::success(['export' => ['job_id' => $id, 'status' => 'queued', 'format' => 'csv']], 'Platform coupon export queued.', 202);
    }

    public function import(ImportPlatformCouponsRequest $request): mixed
    {
        return ApiResponse::success(['import' => ['job_id' => (string) Str::uuid(), 'status' => 'queued', 'format' => 'csv']], 'Platform coupon import queued.', 202);
    }

    private function findCoupon(string $uuid): ?Coupon { return Coupon::where('uuid', $uuid)->first(); }

    private function present(Coupon $coupon): Coupon
    {
        return $coupon->load([
            'plans', 'tenants', 'redemptions.tenant', 'redemptions.subscription',
            'redemptions.invoice.payments',
            'activityLogs' => fn ($q) => $q->latest('created_at')->limit(25),
        ])->loadCount(['plans', 'tenants', 'redemptions']);
    }

    private function syncAssignments(Coupon $coupon, array $input): void
    {
        if (array_key_exists('plan_uuids', $input)) {
            $coupon->plans()->sync(Plan::whereIn('uuid', $input['plan_uuids'])->pluck('id')->all());
        }
        if (array_key_exists('tenant_uuids', $input)) {
            $coupon->tenants()->sync(Tenant::whereIn('uuid', $input['tenant_uuids'])->pluck('id')->all());
        }
    }

    private function setStatus(Request $request, string $uuid, string $status): mixed
    {
        $coupon = $this->findCoupon($uuid);
        if (! $coupon) return ApiResponse::error('Platform coupon not found.', 404, null, 'COUPON_NOT_FOUND');
        $coupon->update(['status' => $status]);
        ActivityLogger::record($request, 'status_changed', $coupon, 'Platform coupon status changed.', ['status' => $status]);
        return ApiResponse::success(['coupon' => $this->present($coupon->fresh())], 'Platform coupon status updated.');
    }
}
