<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveMonitoringRequest;
use App\Http\Requests\StoreSystemIncidentRequest;
use App\Http\Requests\UpdateSystemIncidentRequest;
use App\Models\ApiRequestLog;
use App\Models\MonitoringAlert;
use App\Models\MonitoringService;
use App\Models\MonitoringServiceLog;
use App\Models\QueueJobLog;
use App\Models\SchedulerLog;
use App\Models\SystemIncident;
use App\Models\TenantUsageSnapshot;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PlatformMonitoringController extends Controller
{
    public function services(Request $request): mixed
    {
        $query = MonitoringService::query()->with('logs')->latest('created_at');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('service_type')) $query->where('service_type', $request->string('service_type'));
        return $this->paginated($query, $request, 'Monitoring services fetched.');
    }

    public function serviceLogs(Request $request, string $serviceCode): mixed
    {
        $service = MonitoringService::where('code', $serviceCode)->first();
        if (! $service) return ApiResponse::error('Monitoring service not found.', 404, null, 'MONITORING_SERVICE_NOT_FOUND');
        $query = $service->logs()->with('service')->latest('checked_at');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        return $this->paginated($query, $request, 'Monitoring service logs fetched.');
    }

    public function apiRequestLogs(Request $request): mixed
    {
        $query = ApiRequestLog::query()->with(['tenant', 'user'])->latest('created_at');
        if ($request->filled('tenant_id')) $query->where('tenant_id', $request->integer('tenant_id'));
        if ($request->filled('status_code')) $query->where('status_code', $request->integer('status_code'));
        if ($request->filled('method')) $query->where('method', $request->string('method'));
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('path', 'like', '%'.$search.'%')
                ->orWhere('method', 'like', '%'.$search.'%')
                ->orWhere('ip_address', 'like', '%'.$search.'%'));
        }
        return $this->paginated($query, $request, 'API request logs fetched.');
    }

    public function queueJobs(Request $request): mixed
    {
        $query = QueueJobLog::query()->latest('id');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('queue')) $query->where('queue', $request->string('queue'));
        return $this->paginated($query, $request, 'Queue job logs fetched.');
    }

    public function showQueueJob(int $jobId): mixed
    {
        $job = QueueJobLog::query()->find($jobId);
        if (! $job) return ApiResponse::error('Queue job not found.', 404, null, 'QUEUE_JOB_NOT_FOUND');
        return ApiResponse::success(['job' => $job], 'Queue job fetched.');
    }

    public function retryQueueJob(Request $request, int $jobId): mixed
    {
        $job = QueueJobLog::find($jobId);
        if (! $job) return ApiResponse::error('Queue job not found.', 404, null, 'QUEUE_JOB_NOT_FOUND');
        if (in_array($job->status, ['retry_queued', 'running', 'succeeded'], true)) return ApiResponse::success($job, 'Queue job is already in a non-retryable state.');
        $job->forceFill(['status' => 'retry_queued', 'attempts' => ((int) $job->attempts) + 1])->save();
        ActivityLogger::record($request, 'queue_job.retry_queued', $job, 'Queue job retry requested.', ['attempts' => $job->attempts]);
        return ApiResponse::success($job->fresh(), 'Queue job retry queued.');
    }

    public function deleteQueueJob(Request $request, int $jobId): mixed
    {
        $job = QueueJobLog::find($jobId);
        if (! $job) return ApiResponse::error('Queue job not found.', 404, null, 'QUEUE_JOB_NOT_FOUND');
        if ($job->status !== 'deleted') {
            $job->forceFill(['status' => 'deleted'])->save();
            ActivityLogger::record($request, 'queue_job.deleted', $job, 'Queue job marked deleted.');
        }
        return ApiResponse::success(null, 'Queue job deleted successfully.');
    }

    public function schedulerLogs(Request $request): mixed
    {
        $query = SchedulerLog::query()->latest('id');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('command')) $query->where('command', 'like', '%'.$request->string('command').'%');
        return $this->paginated($query, $request, 'Scheduler logs fetched.');
    }

    public function alerts(Request $request): mixed
    {
        $query = MonitoringAlert::query()->with(['alertable', 'resolvedBy'])->latest('triggered_at');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('severity')) $query->where('severity', $request->string('severity'));
        return $this->paginated($query, $request, 'Monitoring alerts fetched.');
    }

    public function resolveAlert(Request $request, ResolveMonitoringRequest $resolveRequest, int $alertId): mixed
    {
        $alert = MonitoringAlert::find($alertId);
        if (! $alert) return ApiResponse::error('Monitoring alert not found.', 404, null, 'MONITORING_ALERT_NOT_FOUND');
        if ($alert->status === 'resolved') return ApiResponse::success($alert->fresh()->load(['alertable', 'resolvedBy']), 'Monitoring alert is already resolved.');
        $alert->forceFill(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => $request->user()->id, 'resolution_notes' => $resolveRequest->validated('resolution_notes')])->save();
        ActivityLogger::record($request, 'monitoring_alert.resolved', $alert, 'Monitoring alert resolved.', $resolveRequest->validated());
        return ApiResponse::success($alert->fresh()->load(['alertable', 'resolvedBy']), 'Monitoring alert resolved.');
    }

    public function incidents(Request $request): mixed
    {
        $query = SystemIncident::query()->with(['resolvedBy', 'activityLogs.actorPlatformUser', 'activityLogs.actorUser'])->latest('created_at');
        foreach (['status', 'severity'] as $field) if ($request->filled($field)) $query->where($field, $request->string($field));
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')
                ->orWhere('summary', 'like', '%'.$search.'%')
                ->orWhere('severity', 'like', '%'.$search.'%'));
        }
        return $this->paginated($query, $request, 'System incidents fetched.');
    }

    public function storeIncident(StoreSystemIncidentRequest $request): mixed
    {
        $incident = SystemIncident::create($request->validated() + ['status' => $request->validated('status', 'open')]);
        ActivityLogger::record($request, 'system_incident.created', $incident, 'System incident created.', $request->validated());
        return ApiResponse::success(['incident' => $incident->fresh()->load(['activityLogs', 'resolvedBy'])], 'System incident created successfully.', 201);
    }

    public function showIncident(int $incidentId): mixed
    {
        $incident = SystemIncident::with(['activityLogs.actorPlatformUser', 'activityLogs.actorUser', 'resolvedBy'])->find($incidentId);
        if (! $incident) return ApiResponse::error('System incident not found.', 404, null, 'SYSTEM_INCIDENT_NOT_FOUND');
        return ApiResponse::success(['incident' => $incident], 'System incident fetched.');
    }

    public function updateIncident(UpdateSystemIncidentRequest $request, int $incidentId): mixed
    {
        $incident = SystemIncident::find($incidentId);
        if (! $incident) return ApiResponse::error('System incident not found.', 404, null, 'SYSTEM_INCIDENT_NOT_FOUND');
        $data = $request->validated();
        $incident->update($data);
        ActivityLogger::record($request, 'system_incident.updated', $incident, 'System incident updated.', $data);
        return ApiResponse::success(['incident' => $incident->fresh()->load(['activityLogs', 'resolvedBy'])], 'System incident updated successfully.');
    }

    public function resolveIncident(Request $request, ResolveMonitoringRequest $resolveRequest, int $incidentId): mixed
    {
        $incident = SystemIncident::find($incidentId);
        if (! $incident) return ApiResponse::error('System incident not found.', 404, null, 'SYSTEM_INCIDENT_NOT_FOUND');
        if ($incident->status === 'resolved') return ApiResponse::success(['incident' => $incident->fresh()->load(['activityLogs', 'resolvedBy'])], 'System incident is already resolved.');
        $incident->update(['status' => 'resolved', 'resolved_at' => now(), 'resolved_by' => $request->user()->id, 'resolution_notes' => $resolveRequest->validated('resolution_notes')]);
        ActivityLogger::record($request, 'system_incident.resolved', $incident, 'System incident resolved.', $resolveRequest->validated());
        return ApiResponse::success(['incident' => $incident->fresh()->load(['activityLogs', 'resolvedBy'])], 'System incident resolved.');
    }

    public function usageSnapshots(Request $request): mixed
    {
        $query = TenantUsageSnapshot::query()->with('tenant')->latest('period_end');
        if ($request->filled('tenant_id')) $query->where('tenant_id', $request->integer('tenant_id'));
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('organization_name', 'like', '%'.$search.'%')
                ->orWhere('display_name', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%'));
        }
        return $this->paginated($query, $request, 'Tenant usage snapshots fetched.');
    }

    private function paginated($query, Request $request, string $message): mixed
    {
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), $message, 200, ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]);
    }
}

