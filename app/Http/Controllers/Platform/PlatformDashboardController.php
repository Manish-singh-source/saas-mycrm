<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Shared\BaseApiController;
use App\Services\Platform\PlatformAdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlatformDashboardController extends BaseApiController
{
    public function __construct(private readonly PlatformAdminService $admin) {}

    public function summary()
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        return $this->success([
            'tenants' => [
                'total' => DB::table('tenants')->whereNull('deleted_at')->count(),
                'active' => DB::table('tenants')->where('status', 'active')->whereNull('deleted_at')->count(),
                'trial' => DB::table('tenants')->where('status', 'trial')->whereNull('deleted_at')->count(),
                'suspended' => DB::table('tenants')->where('status', 'suspended')->whereNull('deleted_at')->count(),
                'expired' => DB::table('tenants')->where('status', 'expired')->whereNull('deleted_at')->count(),
                'new_today' => DB::table('tenants')->where('created_at', '>=', $today)->count(),
                'new_this_week' => DB::table('tenants')->where('created_at', '>=', now()->startOfWeek())->count(),
                'new_this_month' => DB::table('tenants')->where('created_at', '>=', $month)->count(),
            ],
            'revenue' => [
                'mrr' => (string) DB::table('subscriptions')->whereIn('status', ['active', 'trial'])->where('billing_cycle', 'monthly')->sum('payable_amount'),
                'arr' => (string) (DB::table('subscriptions')->whereIn('status', ['active', 'trial'])->where('billing_cycle', 'monthly')->sum('payable_amount') * 12),
                'collected_today' => (string) DB::table('platform_payments')->where('payment_status', 'paid')->where('paid_at', '>=', $today)->sum('amount'),
                'collected_this_month' => (string) DB::table('platform_payments')->where('payment_status', 'paid')->where('paid_at', '>=', $month)->sum('amount'),
                'currency' => 'INR',
            ],
            'billing' => [
                'overdue_invoice_count' => DB::table('platform_invoices')->where('status', 'overdue')->orWhere(fn ($q) => $q->where('balance_amount', '>', 0)->whereDate('due_date', '<', now()))->count(),
                'overdue_balance' => (string) DB::table('platform_invoices')->where('balance_amount', '>', 0)->whereDate('due_date', '<', now())->sum('balance_amount'),
                'failed_payment_count' => DB::table('platform_payments')->where('payment_status', 'failed')->count(),
            ],
            'operations' => [
                'open_incidents' => DB::table('system_incidents')->whereNull('resolved_at')->count(),
                'critical_security_events' => DB::table('security_events')->where('severity', 'critical')->where('created_at', '>=', now()->subDays(7))->count(),
                'failed_queue_jobs' => DB::table('queue_job_logs')->where('status', 'failed')->count(),
                'failed_scheduler_runs' => DB::table('scheduler_logs')->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function charts()
    {
        return $this->success([
            'tenant_growth' => DB::table('tenants')->selectRaw("DATE(created_at) as date, COUNT(*) as count")->groupBy('date')->orderBy('date')->limit(30)->get(),
            'revenue' => DB::table('platform_payments')->selectRaw("DATE(paid_at) as date, SUM(amount) as amount")->where('payment_status', 'paid')->groupBy('date')->orderBy('date')->limit(30)->get(),
            'tenant_status' => DB::table('tenants')->selectRaw('status, COUNT(*) as count')->whereNull('deleted_at')->groupBy('status')->get(),
        ]);
    }

    public function recent()
    {
        return $this->success([
            'recent_tenants' => DB::table('tenants')->whereNull('deleted_at')->latest('id')->limit(10)->get(['uuid', 'organization_name', 'slug', 'status', 'created_at']),
            'recent_payments' => DB::table('platform_payments')->join('tenants', 'tenants.id', '=', 'platform_payments.tenant_id')->latest('platform_payments.id')->limit(10)->get(['platform_payments.uuid', 'tenants.organization_name', 'amount', 'currency', 'payment_status', 'paid_at']),
            'overdue_invoices' => DB::table('platform_invoices')->join('tenants', 'tenants.id', '=', 'platform_invoices.tenant_id')->where('balance_amount', '>', 0)->whereDate('due_date', '<', now())->latest('platform_invoices.id')->limit(10)->get(['platform_invoices.uuid', 'invoice_number', 'tenants.organization_name', 'balance_amount', 'due_date', 'platform_invoices.status']),
        ]);
    }

    public function alerts()
    {
        return $this->success([
            'alerts' => DB::table('monitoring_alerts')->where('status', 'open')->latest('id')->limit(20)->get(),
            'security_events' => DB::table('security_events')->latest('id')->limit(20)->get(),
        ]);
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
}
