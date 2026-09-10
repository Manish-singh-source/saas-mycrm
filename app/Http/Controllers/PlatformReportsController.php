<?php
namespace App\Http\Controllers;

use App\Models\ReportExportJob;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformReportsController extends Controller
{
    private const CODES = ['tenant-status', 'plan-performance', 'revenue', 'invoice-aging', 'payment-failures', 'coupon-usage', 'tenant-usage', 'support-sla', 'security-events'];

    public function index(string $report_code, Request $request): mixed
    {
        if (!in_array($report_code, self::CODES, true)) throw new HttpResponseException(ApiResponse::error('Unknown report.', 404, null, 'REPORT_NOT_FOUND'));
        $data = match ($report_code) {
            'tenant-status' => DB::table('tenants')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->orderBy('status')->get(),
            'plan-performance' => DB::table('subscriptions')->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')->select('plans.name as plan_name', DB::raw('COUNT(subscriptions.id) as subscriptions'), DB::raw('COALESCE(SUM(subscriptions.payable_amount), 0) as revenue'))->groupBy('plans.name')->orderBy('plans.name')->get(),
            'revenue' => DB::table('platform_payments')->whereIn('payment_status', ['success', 'succeeded', 'paid'])->select('currency', DB::raw('COUNT(*) as payments'), DB::raw('SUM(amount) as total'))->groupBy('currency')->orderBy('currency')->get(),
            'invoice-aging' => DB::table('platform_invoices')->where('balance_amount', '>', 0)->select('status', DB::raw('COUNT(*) as invoices'), DB::raw('SUM(balance_amount) as outstanding'))->groupBy('status')->orderBy('status')->get(),
            'payment-failures' => DB::table('platform_payments')->whereIn('payment_status', ['failed', 'failure'])->select('gateway', 'payment_status as status', DB::raw('COUNT(*) as total'))->groupBy('gateway', 'payment_status')->orderBy('gateway')->get(),
            'coupon-usage' => DB::table('coupon_redemptions')->select('coupon_id', DB::raw('COUNT(*) as redemptions'), DB::raw('SUM(discount_amount) as discount_total'))->groupBy('coupon_id')->orderBy('coupon_id')->get(),
            'tenant-usage' => DB::table('tenant_usage_snapshots')->join('tenants', 'tenants.id', '=', 'tenant_usage_snapshots.tenant_id')->select('tenant_usage_snapshots.*', 'tenants.uuid as tenant_uuid', 'tenants.organization_name')->latest('tenant_usage_snapshots.id')->limit(100)->get(),
            'support-sla' => DB::table('platform_tickets')->select('status', 'priority', DB::raw('COUNT(*) as total'))->groupBy('status', 'priority')->orderBy('status')->get(),
            'security-events' => DB::table('security_events')->select('severity', 'event', DB::raw('COUNT(*) as total'))->groupBy('severity', 'event')->orderBy('severity')->get(),
        };
        return ApiResponse::success(['report' => $report_code, 'data' => $data, 'filters' => $request->query()], 'Report fetched.');
    }

    public function jobs(Request $request): mixed
    {
        $page = ReportExportJob::with(['creator', 'file'])->latest('id')->paginate(min(max((int) $request->input('per_page', 25), 1), 100));
        return ApiResponse::success($page->items(), 'Report export jobs fetched.', 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }

    public function job(string $job_uuid): mixed
    {
        $job = ReportExportJob::with(['creator', 'file'])->where('uuid', $job_uuid)->first() ?? throw new HttpResponseException(ApiResponse::error('Export job not found.', 404));
        return ApiResponse::success(['export' => $job], 'Report export job fetched.');
    }

    public function export(Request $request, string $report_code): mixed
    {
        if (!in_array($report_code, self::CODES, true)) throw new HttpResponseException(ApiResponse::error('Unknown report.', 404, null, 'REPORT_NOT_FOUND'));
        $format = $request->input('format', 'csv');
        if (!in_array($format, ['csv', 'xlsx', 'json'], true)) return ApiResponse::validation(['format' => ['The format is invalid.']]);
        $job = ReportExportJob::create(['uuid' => (string) Str::uuid(), 'report_code' => $report_code, 'format' => $format, 'filters' => $request->query(), 'status' => 'queued', 'created_by' => $request->user()->id]);
        return ApiResponse::success(['export' => $job], 'Report export queued.', 201);
    }
}