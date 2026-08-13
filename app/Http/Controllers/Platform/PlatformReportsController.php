<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformReportsController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops) {}

    public function report(Request $request, string $code)
    {
        $data = match ($code) {
            'tenant-status' => DB::table('tenants')->selectRaw('status, COUNT(*) as total')->groupBy('status')->get(),
            'plan-performance' => DB::table('subscriptions')->join('plans', 'plans.id', '=', 'subscriptions.plan_id')->selectRaw('plans.name, plans.code, COUNT(*) as subscriptions, SUM(subscriptions.payable_amount) as revenue')->groupBy('plans.name', 'plans.code')->get(),
            'revenue' => DB::table('platform_payments')->whereIn('payment_status', ['success', 'paid', 'completed'])->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->get(),
            'invoice-aging' => DB::table('platform_invoices')->where('balance_amount', '>', 0)->selectRaw('status, COUNT(*) as invoices, SUM(balance_amount) as balance')->groupBy('status')->get(),
            'payment-failures' => DB::table('platform_payments')->whereNotIn('payment_status', ['success', 'paid', 'completed'])->selectRaw('gateway, payment_status, COUNT(*) as total')->groupBy('gateway', 'payment_status')->get(),
            'coupon-usage' => DB::table('coupon_redemptions')->join('coupons', 'coupons.id', '=', 'coupon_redemptions.coupon_id')->selectRaw('coupons.code, COUNT(*) as redemptions, SUM(discount_amount) as discount_amount')->groupBy('coupons.code')->get(),
            'tenant-usage' => DB::table('tenant_usage_snapshots')->latest('id')->limit(100)->get(),
            'support-sla' => DB::table('platform_tickets')->selectRaw('status, priority, COUNT(*) as total')->groupBy('status', 'priority')->get(),
            'security-events' => DB::table('security_events')->selectRaw('severity, event, COUNT(*) as total')->groupBy('severity', 'event')->get(),
            default => abort(404),
        };
        return $this->success(['report' => $code, 'data' => $data, 'filters' => $request->query()]);
    }

    public function export(Request $request, string $code)
    {
        $d = $request->validate(['format' => ['nullable', 'string']]);
        $id = DB::table('report_export_jobs')->insertGetId(['uuid' => (string) Str::uuid(), 'report_code' => $code, 'format' => $d['format'] ?? 'csv', 'filters' => json_encode($request->query()), 'status' => 'queued', 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $job = DB::table('report_export_jobs')->where('id', $id)->first();
        $this->ops->audit($request, 'report_export_queued', 'report_export_jobs', $id, null, (array) $job);
        return $this->success(['export' => $job], 'Report export queued.', 201);
    }

    public function exportJobs(Request $request)
    {
        $p = DB::table('report_export_jobs')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function exportJob(string $job_uuid)
    {
        return $this->success(['export' => $this->ops->byUuid('report_export_jobs', $job_uuid)]);
    }
}
