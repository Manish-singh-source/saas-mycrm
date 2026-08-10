<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformMonitoringController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops) {}

    public function services(Request $request) { $p = DB::table('monitoring_services')->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function serviceLogs(Request $request, string $service_code) { $service = DB::table('monitoring_services')->where('code', $service_code)->first(); abort_if(! $service, 404); $p = DB::table('monitoring_service_logs')->where('service_id', $service->id)->latest('checked_at')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function apiRequestLogs(Request $request) { $q = DB::table('api_request_logs'); if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->ops->tenantId($request->input('tenant_uuid'))); if ($request->filled('status_code')) $q->where('status_code', $request->input('status_code')); $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function queueJobs(Request $request) { $q = DB::table('queue_job_logs'); if ($request->filled('status')) $q->where('status', $request->input('status')); $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function retryQueueJob(Request $request, int $job_id) { $job = DB::table('queue_job_logs')->where('id', $job_id)->first(); abort_if(! $job, 404); DB::table('queue_job_logs')->where('id', $job_id)->update(['status' => 'retry_queued', 'attempts' => DB::raw('attempts + 1')]); $this->ops->audit($request, 'queue_job_retry_queued', 'queue_job_logs', $job_id, (array) $job, ['status' => 'retry_queued']); return $this->success(['job' => DB::table('queue_job_logs')->where('id', $job_id)->first()], 'Queue job retry queued.'); }
    public function deleteQueueJob(Request $request, int $job_id) { $job = DB::table('queue_job_logs')->where('id', $job_id)->first(); abort_if(! $job, 404); DB::table('queue_job_logs')->where('id', $job_id)->update(['status' => 'deleted']); $this->ops->audit($request, 'queue_job_deleted', 'queue_job_logs', $job_id, (array) $job, ['status' => 'deleted']); return $this->success(null, 'Queue job marked deleted.'); }
    public function schedulerLogs(Request $request) { $p = DB::table('scheduler_logs')->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function alerts(Request $request) { $q = DB::table('monitoring_alerts'); if ($request->filled('status')) $q->where('status', $request->input('status')); $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function resolveAlert(Request $request, int|string $alert_id)
    {
        $alert = is_numeric($alert_id) ? DB::table('monitoring_alerts')->where('id', $alert_id)->first() : DB::table('monitoring_alerts')->where('uuid', $alert_id)->first();
        abort_if(! $alert, 404);
        $d = $request->validate(['notes' => ['required', 'string']]);
        DB::table('monitoring_alerts')->where('id', $alert->id)->update(['status' => 'resolved', 'resolved_at' => now(), 'resolution_notes' => $d['notes'], 'resolved_by' => $request->user()?->id]);
        $this->ops->audit($request, 'monitoring_alert_resolved', 'monitoring_alerts', $alert->id, (array) $alert, ['status' => 'resolved'], $d['notes']);
        return $this->success(['alert' => DB::table('monitoring_alerts')->where('id', $alert->id)->first()], 'Alert resolved.');
    }
    public function incidents(Request $request) { $p = DB::table('system_incidents')->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
    public function storeIncident(Request $request) { $d = $request->validate(['title' => ['required', 'string'], 'severity' => ['required', 'string'], 'status' => ['nullable', 'string'], 'summary' => ['nullable', 'string']]); $id = DB::table('system_incidents')->insertGetId([...$d, 'status' => $d['status'] ?? 'open', 'started_at' => now(), 'created_at' => now(), 'updated_at' => now()]); $this->ops->audit($request, 'system_incident_created', 'system_incidents', $id, null, $d); return $this->success(['incident' => DB::table('system_incidents')->where('id', $id)->first()], 'Incident created.', 201); }
    public function showIncident(int $incident_id) { $incident = DB::table('system_incidents')->where('id', $incident_id)->first(); abort_if(! $incident, 404); return $this->success(['incident' => $incident, 'audit' => DB::table('activity_logs')->where('subject_type', 'system_incidents')->where('subject_id', $incident->id)->get()]); }
    public function updateIncident(Request $request, int $incident_id) { $incident = DB::table('system_incidents')->where('id', $incident_id)->first(); abort_if(! $incident, 404); $d = $request->validate(['title' => ['sometimes', 'string'], 'severity' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'summary' => ['nullable', 'string']]); DB::table('system_incidents')->where('id', $incident_id)->update([...$d, 'updated_at' => now()]); $this->ops->audit($request, 'system_incident_updated', 'system_incidents', $incident_id, (array) $incident, $d); return $this->success(['incident' => DB::table('system_incidents')->where('id', $incident_id)->first()], 'Incident updated.'); }
    public function resolveIncident(Request $request, int $incident_id) { $incident = DB::table('system_incidents')->where('id', $incident_id)->first(); abort_if(! $incident, 404); $d = $request->validate(['notes' => ['required', 'string']]); DB::table('system_incidents')->where('id', $incident_id)->update(['status' => 'resolved', 'resolved_at' => now(), 'resolution_notes' => $d['notes'], 'resolved_by' => $request->user()?->id, 'updated_at' => now()]); $this->ops->audit($request, 'system_incident_resolved', 'system_incidents', $incident_id, (array) $incident, ['status' => 'resolved'], $d['notes']); return $this->success(['incident' => DB::table('system_incidents')->where('id', $incident_id)->first()], 'Incident resolved.'); }
    public function tenantUsageSnapshots(Request $request) { $q = DB::table('tenant_usage_snapshots'); if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->ops->tenantId($request->input('tenant_uuid'))); $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25)); return $this->list($p->items(), $p); }
}
