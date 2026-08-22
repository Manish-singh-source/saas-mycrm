<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PlatformDashboardController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function summary(Request $request)
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $tenantQuery = $this->dateFiltered(DB::table('tenants')->whereNull('deleted_at'), $request, 'created_at');
        $subscriptionQuery = $this->dateFiltered(DB::table('subscriptions')->whereNull('deleted_at'), $request, 'created_at');
        $paymentQuery = $this->dateFiltered(DB::table('platform_payments'), $request, 'paid_at');
        $invoiceQuery = $this->dateFiltered(DB::table('platform_invoices')->whereNull('deleted_at'), $request, 'due_date');
        $incidentQuery = $this->dateFiltered(DB::table('system_incidents'), $request, 'created_at');
        $queueJobQuery = $this->dateFiltered(DB::table('queue_job_logs'), $request, 'created_at');

        $monthlyMrr = (clone $subscriptionQuery)->whereIn('status', ['active', 'trial'])->where('billing_cycle', 'monthly')->sum('payable_amount');
        $overdueInvoices = (clone $invoiceQuery)->where(fn (Builder $query) => $query->where('status', 'overdue')->orWhere(fn (Builder $q) => $q->where('balance_amount', '>', 0)->whereDate('due_date', '<', now())));

        return $this->success([
            'tenants' => [
                'total' => (clone $tenantQuery)->count(),
                'active' => (clone $tenantQuery)->where('status', 'active')->count(),
                'trial' => (clone $tenantQuery)->where('status', 'trial')->count(),
                'suspended' => (clone $tenantQuery)->where('status', 'suspended')->count(),
                'expired' => (clone $tenantQuery)->where('status', 'expired')->count(),
                'new_today' => DB::table('tenants')->whereNull('deleted_at')->where('created_at', '>=', $today)->count(),
                'new_this_week' => DB::table('tenants')->whereNull('deleted_at')->where('created_at', '>=', now()->startOfWeek())->count(),
                'new_this_month' => DB::table('tenants')->whereNull('deleted_at')->where('created_at', '>=', $month)->count(),
            ],
            'revenue' => [
                'mrr' => (string) $monthlyMrr,
                'arr' => (string) ($monthlyMrr * 12),
                'collected_today' => (string) DB::table('platform_payments')->whereIn('payment_status', ['paid', 'success', 'completed'])->where('paid_at', '>=', $today)->sum('amount'),
                'collected_this_month' => (string) DB::table('platform_payments')->whereIn('payment_status', ['paid', 'success', 'completed'])->where('paid_at', '>=', $month)->sum('amount'),
                'currency' => 'INR',
            ],
            'billing' => [
                'overdue_invoice_count' => (clone $overdueInvoices)->count(),
                'overdue_balance' => (string) (clone $invoiceQuery)->where('balance_amount', '>', 0)->whereDate('due_date', '<', now())->sum('balance_amount'),
                'failed_payment_count' => (clone $paymentQuery)->where('payment_status', 'failed')->count(),
            ],
            'operations' => [
                'open_incidents' => (clone $incidentQuery)->whereNull('resolved_at')->count(),
                'critical_security_events' => $this->dateFiltered(DB::table('security_events')->where('severity', 'critical'), $request, 'created_at')->count(),
                'failed_queue_jobs' => (clone $queueJobQuery)->where('status', 'failed')->count(),
                'failed_scheduler_runs' => $this->dateFiltered(DB::table('scheduler_logs')->where('status', 'failed'), $request, 'created_at')->count(),
            ],
        ]);
    }

    public function charts(Request $request)
    {
        $subscriptionStatus = $this->subscriptionStatus();

        return $this->success([
            'tenant_growth' => $this->tenantGrowth($request),
            'revenue' => $this->revenue($request),
            'plan_distribution' => $this->planDistribution($request),
            'subscription_status' => $subscriptionStatus,
            'tenant_status' => $subscriptionStatus,
            'usage' => $this->usage($request),
        ]);
    }

    public function chart(Request $request, string $chart)
    {
        return match ($chart) {
            'tenant-growth' => $this->success($this->tenantGrowth($request)),
            'revenue' => $this->success($this->revenue($request)),
            'api-usage-trend', 'storage-usage-trend', 'usage' => $this->success($this->usage($request)),
            'payment-success-failure-trend' => $this->success($this->revenue($request)),
            'plan-distribution' => $this->success($this->planDistribution($request)),
            'subscription-status' => $this->success($this->subscriptionStatus()),
            default => $this->businessError('Dashboard chart not found.', 'DASHBOARD_CHART_NOT_FOUND', Response::HTTP_NOT_FOUND),
        };
    }

    public function recent()
    {
        return $this->success([
            'recent_tenants' => $this->recentTenantsRows(),
            'recent_payments' => $this->recentPaymentsRows(),
            'overdue_invoices' => $this->overdueInvoiceRows(),
        ]);
    }

    public function recentTenants()
    {
        return $this->success($this->recentTenantsRows());
    }

    public function recentPayments()
    {
        return $this->success($this->recentPaymentsRows());
    }

    public function overdueInvoices()
    {
        return $this->success($this->overdueInvoiceRows());
    }

    public function alerts()
    {
        $alerts = $this->activeAlertRows();

        return $this->success([
            'alerts' => $alerts,
            'active_alerts' => $alerts,
            'security_events' => $this->securityEventRows(),
        ]);
    }

    public function activeAlerts()
    {
        return $this->success($this->activeAlertRows());
    }

    public function securityEvents()
    {
        return $this->success($this->securityEventRows());
    }

    public function export(Request $request)
    {
        $rows = DB::table('tenants')->whereNull('deleted_at')->get(['uuid', 'organization_name', 'slug', 'status', 'created_at']);
        $csv = "uuid,organization_name,slug,status,created_at\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', (array) $row))."\n";
        }

        $path = 'platform/exports/dashboard-'.now()->format('YmdHis').'.csv';
        Storage::disk(config('filesystems.default', 'local'))->put($path, $csv);
        $id = DB::table('files')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'disk' => config('filesystems.default', 'local'),
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'text/csv',
            'extension' => 'csv',
            'size_bytes' => strlen($csv),
            'checksum' => hash('sha256', $csv),
            'visibility' => 'private',
            'platform_uploaded_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $file = DB::table('files')->where('id', $id)->first();
        $this->admin->audit($request, 'platform.dashboard_exported', 'file', $id, null, ['uuid' => $file->uuid, 'original_name' => $file->original_name]);

        return $this->success(['file' => ['uuid' => $file->uuid, 'original_name' => $file->original_name, 'mime_type' => $file->mime_type, 'size_bytes' => $file->size_bytes]], 'Dashboard export created.', 201);
    }

    private function tenantGrowth(Request $request)
    {
        return $this->dateFiltered(DB::table('tenants')->whereNull('deleted_at'), $request, 'created_at')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();
    }

    private function revenue(Request $request)
    {
        return $this->dateFiltered(DB::table('platform_payments')->whereIn('payment_status', ['paid', 'success', 'completed', 'failed']), $request, 'paid_at')
            ->selectRaw("DATE(paid_at) as date, SUM(CASE WHEN payment_status IN ('paid', 'success', 'completed') THEN amount ELSE 0 END) as amount, SUM(CASE WHEN payment_status IN ('paid', 'success', 'completed') THEN 1 ELSE 0 END) as success, SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->whereNotNull('paid_at')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();
    }

    private function planDistribution(Request $request)
    {
        return $this->dateFiltered(DB::table('subscriptions'), $request, 'subscriptions.created_at')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereNull('subscriptions.deleted_at')
            ->selectRaw('plans.name as name, plans.code as code, COUNT(*) as count, SUM(subscriptions.payable_amount) as revenue')
            ->groupBy('plans.name', 'plans.code')
            ->orderByDesc('count')
            ->get();
    }

    private function subscriptionStatus()
    {
        return DB::table('subscriptions')
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    private function usage(Request $request)
    {
        return $this->dateFiltered(DB::table('tenant_usage_snapshots'), $request, 'period_start')
            ->selectRaw('period_start as date, SUM(api_requests) as api, ROUND(SUM(storage_bytes) / 1099511627776, 2) as storage, SUM(users_count) as users, SUM(projects_count) as projects, SUM(invoices_count) as invoices')
            ->groupBy('period_start')
            ->orderBy('period_start')
            ->limit(30)
            ->get();
    }

    private function recentTenantsRows()
    {
        $latestSubscriptions = DB::table('subscriptions')
            ->selectRaw('MAX(id) as id, tenant_id')
            ->whereNull('deleted_at')
            ->groupBy('tenant_id');

        return DB::table('tenants')
            ->leftJoin('users as owners', fn ($join) => $join->on('owners.tenant_id', '=', 'tenants.id')->where('owners.account_type', '=', 'owner'))
            ->leftJoinSub($latestSubscriptions, 'latest_subscriptions', fn ($join) => $join->on('latest_subscriptions.tenant_id', '=', 'tenants.id'))
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'latest_subscriptions.id')
            ->leftJoin('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereNull('tenants.deleted_at')
            ->latest('tenants.id')
            ->limit(5)
            ->get([
                'tenants.uuid',
                'tenants.organization_name',
                'tenants.slug',
                'owners.display_name as owner_name',
                'owners.email as owner_email',
                'plans.name as plan_name',
                'subscriptions.status as subscription_status',
                'tenants.status',
                'tenants.created_at',
            ]);
    }

    private function recentPaymentsRows()
    {
        return DB::table('platform_payments')
            ->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')
            ->latest('platform_payments.id')
            ->limit(5)
            ->get([
                'platform_payments.uuid',
                'platform_payments.payment_number',
                'tenants.organization_name',
                'tenants.organization_name as tenant_name',
                'platform_payments.amount',
                'platform_payments.currency',
                'platform_payments.gateway',
                'platform_payments.payment_status',
                'platform_payments.paid_at',
            ]);
    }

    private function overdueInvoiceRows()
    {
        return DB::table('platform_invoices')
            ->join('tenants', 'tenants.id', '=', 'platform_invoices.tenant_id')
            ->whereNull('platform_invoices.deleted_at')
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '<', now())
            ->latest('platform_invoices.id')
            ->limit(5)
            ->get([
                'platform_invoices.uuid',
                'platform_invoices.invoice_number',
                'tenants.organization_name',
                'tenants.organization_name as tenant_name',
                'platform_invoices.balance_amount',
                'platform_invoices.currency',
                'platform_invoices.due_date',
                'platform_invoices.status',
            ]);
    }

    private function activeAlertRows()
    {
        return DB::table('monitoring_alerts')
            ->where('status', 'open')
            ->latest('id')
            ->limit(5)
            ->get();
    }

    private function securityEventRows()
    {
        return DB::table('security_events')
            ->leftJoin('tenants', 'tenants.id', '=', 'security_events.tenant_id')
            ->leftJoin('users', 'users.id', '=', 'security_events.user_id')
            ->latest('security_events.id')
            ->limit(5)
            ->get([
                'security_events.id',
                'security_events.event',
                'security_events.severity',
                'security_events.ip_address',
                'security_events.created_at',
                'tenants.organization_name as tenant_name',
                'users.display_name as actor',
            ]);
    }

    private function dateFiltered(Builder $query, Request $request, string $column): Builder
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->input('date_to'));
        }

        return $query;
    }
}
