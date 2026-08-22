<?php

namespace App\Http\Controllers\Tenant;

use App\Services\Tenant\TenantWorkspaceService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantDashboardController extends BaseTenantController
{
    public function __construct(private readonly TenantWorkspaceService $tenant) {}

    public function sidebar(): JsonResponse
    {
        return $this->success(['navigation' => [
            'modules' => app(\App\Tenancy\TenantContext::class)->enabledModules(),
            'subscription' => app(\App\Tenancy\TenantContext::class)->subscription(),
            'badges' => [
                'overdue_tasks' => $this->overdueTasksCount(),
                'open_issues' => $this->tenant->count('client_issues', ['status' => ['open', 'in_progress']]),
                'pending_leave' => $this->tenant->count('leave_requests'),
                'unread_notifications' => $this->unreadNotificationsCount(),
                'renewals_due_soon' => $this->renewalsDueSoonCount(),
            ],
        ]]);
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->success(['summary' => [
            'leads' => $this->dateCount('lead_profiles', $request),
            'clients' => $this->dateCount('client_profiles', $request),
            'vendors' => $this->dateCount('vendor_profiles', $request),
            'active_projects' => $this->dateCount('projects', $request, ['status' => ['active', 'in_progress']]),
            'open_tasks' => $this->dateCount('tasks', $request, ['status' => ['open', 'in_progress']]),
            'open_support_issues' => $this->dateCount('client_issues', $request, ['status' => ['open', 'in_progress']]),
            'staff_count' => $this->dateCount('staff', $request),
            'present_today' => $this->todayAttendanceCount(true),
            'absent_today' => max($this->tenant->count('staff', ['employment_status' => 'active']) - $this->todayAttendanceCount(true), 0),
            'pending_leave_approvals' => $this->dateCount('leave_requests', $request),
        ]]);
    }

    public function chart(Request $request, string $chart): JsonResponse
    {
        $map = [
            'leads-pipeline' => ['table' => 'lead_profiles', 'group' => 'stage_id'],
            'projects' => ['table' => 'projects', 'group' => 'status'],
            'tasks' => ['table' => 'tasks', 'group' => 'status'],
            'attendance' => ['table' => 'attendance_records', 'group' => 'attendance_date'],
            'support' => ['table' => 'client_issues', 'group' => 'status'],
            'revenue' => ['table' => 'tenant_payments', 'group' => 'created_at'],
        ];

        if (in_array($chart, ['revenue', 'payment-success-failure-trend'], true)) {
            return $this->success(['chart' => ['code' => $chart, 'series' => $this->revenueSeries($request)]]);
        }

        abort_unless(isset($map[$chart]), 404, 'Chart not found.');

        return $this->success(['chart' => ['code' => $chart, 'series' => $this->groupedCount($map[$chart]['table'], $map[$chart]['group'], $request)]]);
    }

    public function table(Request $request, string $widget): JsonResponse
    {
        $data = match ($widget) {
            'my-tasks' => $this->recentRows('tasks', ['uuid', 'title', 'status', 'priority', 'due_date'], 'due_date', $request),
            'upcoming-events' => $this->recentRows('calendar_events', ['uuid', 'title', 'starts_at', 'ends_at'], 'starts_at', $request),
            'recent-leads' => $this->recentRows('lead_profiles', ['uuid', 'lead_number', 'stage_id', 'expected_value'], 'id', $request),
            'overdue-invoices' => $this->recentRows('tenant_invoices', ['uuid', 'invoice_number', 'due_date', 'balance_amount'], 'due_date', $request),
            'recent-activities' => $this->recentRows('activity_logs', ['uuid', 'event', 'description', 'created_at'], 'created_at', $request),
            default => abort(404, 'Dashboard widget not found.'),
        };

        return $this->success([str_replace('-', '_', $widget) => $data]);
    }

    public function recentActivities(Request $request): JsonResponse
    {
        $query = DB::table('activity_logs')
            ->leftJoin('users', 'users.id', '=', 'activity_logs.actor_user_id')
            ->where('activity_logs.tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->orderByDesc('activity_logs.created_at')
            ->limit(5);

        return $this->success(['activities' => $this->dateFiltered($query, $request, 'activity_logs.created_at')->get(['activity_logs.*', 'users.uuid as actor_uuid', 'users.display_name as actor_name'])]);
    }

    public function widgets(Request $request): JsonResponse
    {
        $value = DB::table('user_preferences')
            ->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->where('user_id', $request->user()->id)
            ->where('group', 'dashboard')
            ->where('key', 'widgets')
            ->value('value');

        return $this->success(['widgets' => $value ? json_decode((string) $value, true) : $this->defaultWidgets()]);
    }

    public function updateWidgets(Request $request): JsonResponse
    {
        $data = $request->validate(['widgets' => ['required', 'array'], 'widgets.*.code' => ['required', 'string', 'max:100'], 'widgets.*.position' => ['required', 'integer', 'min:1'], 'widgets.*.visible' => ['sometimes', 'boolean'], 'widgets.*.settings' => ['sometimes', 'array']]);
        DB::table('user_preferences')->updateOrInsert(
            ['tenant_id' => app(\App\Tenancy\TenantContext::class)->id(), 'user_id' => $request->user()->id, 'group' => 'dashboard', 'key' => 'widgets'],
            ['value' => json_encode($data['widgets']), 'created_at' => now(), 'updated_at' => now()]
        );

        return $this->success(['widgets' => $data['widgets']], 'Dashboard widgets saved.');
    }

    public function export(Request $request): JsonResponse
    {
        return $this->success(['job' => $this->tenant->createJob($request, 'export', 'dashboard', $request->all())], 'Dashboard export queued.', 202);
    }

    private function dateCount(string $table, Request $request, array $filters = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        foreach ($filters as $column => $value) {
            is_array($value) ? $query->whereIn($column, $value) : $query->where($column, $value);
        }

        return (int) $this->dateFiltered($query, $request)->count();
    }

    private function groupedCount(string $table, string $group, Request $request): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $group)) {
            return [];
        }

        $query = DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());

        return $this->dateFiltered($query, $request)->selectRaw($group.' as label, count(*) as total')->groupBy($group)->orderBy($group)->get()->all();
    }

    private function recentRows(string $table, array $columns, string $order, Request $request): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $select = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));
        $query = DB::table($table)->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());

        return $this->dateFiltered($query, $request)
            ->orderByDesc(Schema::hasColumn($table, $order) ? $order : 'id')
            ->limit(5)
            ->get($select ?: ['*'])
            ->all();
    }

    private function revenueSeries(Request $request): array
    {
        if (! Schema::hasTable('tenant_payments')) {
            return [];
        }

        $query = DB::table('tenant_payments')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());

        return $this->dateFiltered($query, $request)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as label, sum(amount) as total")
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->all();
    }

    private function dateFiltered(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        $from = $query->from;
        $table = is_string($from) && ! str_contains($from, ' ') ? $from : null;
        $plainColumn = str_contains($column, '.') ? substr($column, strrpos($column, '.') + 1) : $column;

        if ($table && ! Schema::hasColumn($table, $plainColumn)) {
            return $query;
        }

        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->input('date_to'));
        }

        return $query;
    }

    private function todayAttendanceCount(bool $present): int
    {
        if (! Schema::hasTable('attendance_records')) {
            return 0;
        }

        return (int) DB::table('attendance_records')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())->whereDate('attendance_date', now()->toDateString())->when($present, fn ($q) => $q->whereNotNull('check_in_at'))->count();
    }

    private function overdueTasksCount(): int
    {
        if (! Schema::hasTable('tasks')) {
            return 0;
        }

        $query = DB::table('tasks')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        if (Schema::hasColumn('tasks', 'status')) {
            $query->whereIn('status', ['open', 'in_progress']);
        }
        if (Schema::hasColumn('tasks', 'due_date')) {
            $query->whereDate('due_date', '<', now()->toDateString());
        }

        return (int) $query->count();
    }

    private function unreadNotificationsCount(): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        return (int) DB::table('notifications')
            ->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id())
            ->whereNull('read_at')
            ->count();
    }

    private function renewalsDueSoonCount(): int
    {
        if (! Schema::hasTable('renewals')) {
            return 0;
        }

        $query = DB::table('renewals')->where('tenant_id', app(\App\Tenancy\TenantContext::class)->id());
        if (Schema::hasColumn('renewals', 'renewal_date')) {
            $query->whereBetween('renewal_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        }
        if (Schema::hasColumn('renewals', 'status')) {
            $query->whereNotIn('status', ['cancelled', 'completed']);
        }

        return (int) $query->count();
    }

    private function defaultWidgets(): array
    {
        return [
            ['code' => 'my_tasks', 'position' => 1, 'visible' => true, 'settings' => ['limit' => 5]],
            ['code' => 'calendar', 'position' => 2, 'visible' => true],
            ['code' => 'notifications', 'position' => 3, 'visible' => true],
        ];
    }
}