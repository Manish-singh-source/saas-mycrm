<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportAuditRequest;
use App\Http\Requests\ListPlatformActivityLogsRequest;
use App\Http\Requests\ReviewSecurityEventRequest;
use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PlatformAuditController extends Controller
{
    public function activityLogs(Request $request): mixed
    {
        $query = ActivityLog::with(['actorPlatformUser', 'actorUser', 'tenant', 'subject'])->latest('created_at');
        if ($request->filled('search')) {
            $term = '%'.(string) $request->input('search').'%';
            $query->where(function ($nested) use ($term): void { $nested->where('event', 'like', $term)->orWhere('subject_type', 'like', $term)->orWhere('request_id', 'like', $term)->orWhere('ip_address', 'like', $term); });
        }
        if ($request->filled('tenant_id')) $query->where('tenant_id', $request->integer('tenant_id'));
        foreach (['event', 'subject_type', 'request_id'] as $field) if ($request->filled($field)) $query->where($field, (string) $request->string($field));
        if ($request->filled('subject_id')) $query->where('subject_id', $request->integer('subject_id'));
        if ($request->filled('actor_platform_user_id')) $query->where('actor_platform_user_id', $request->integer('actor_platform_user_id'));
        if ($request->filled('from')) $query->where('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->where('created_at', '<=', $request->date('to')->endOfDay());
        $this->sort($query, $request, ['created_at', 'event', 'subject_type']);
        return $this->paginate($query, $request, 'Activity logs fetched.');
    }

    public function securityEvents(Request $request): mixed
    {
        $query = SecurityEvent::with(['tenant', 'user'])->latest('created_at');
        if ($request->filled('search')) {
            $term = '%'.(string) $request->input('search').'%';
            $query->where(function ($nested) use ($term): void { $nested->where('event', 'like', $term)->orWhere('severity', 'like', $term)->orWhere('ip_address', 'like', $term); });
        }
        foreach (['tenant_id', 'user_id'] as $field) if ($request->filled($field)) $query->where($field, $request->integer($field));
        foreach (['event', 'severity'] as $field) if ($request->filled($field)) $query->where($field, (string) $request->string($field));
        if ($request->filled('review_status')) $query->whereJsonContains('metadata->review->status', (string) $request->string('review_status'));
        if ($request->filled('from')) $query->where('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->where('created_at', '<=', $request->date('to')->endOfDay());
        $this->sort($query, $request, ['created_at', 'event', 'severity']);
        $page = $query->paginate($this->perPage($request))->withQueryString();
        $items = collect($page->items())->map(fn (SecurityEvent $event) => $this->presentSecurityEvent($event));
        return ApiResponse::success($items->values(), 'Security events fetched.', 200, $this->meta($page));
    }

    public function reviewSecurityEvent(Request $request, ReviewSecurityEventRequest $reviewRequest, int $eventId): mixed
    {
        $event = SecurityEvent::with(['tenant', 'user'])->find($eventId);
        if (! $event) return ApiResponse::error('Security event not found.', 404, null, 'SECURITY_EVENT_NOT_FOUND');
        $metadata = $this->metadata($event);
        $review = $reviewRequest->validated();
        if (isset($metadata['review']) && $metadata['review']['status'] === $review['review_status'] && $metadata['review']['notes'] === $review['review_notes']) {
            return ApiResponse::success($this->presentSecurityEvent($event), 'Security event review already recorded.');
        }
        $metadata['review'] = ['status' => $review['review_status'], 'notes' => $review['review_notes'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()->toISOString()];
        $event->forceFill(['metadata' => json_encode($this->redact($metadata))])->save();
        ActivityLogger::record($request, 'security_event.reviewed', $event, 'Security event reviewed.', ['review_status' => $review['review_status']]);
        return ApiResponse::success($this->presentSecurityEvent($event->fresh()->load(['tenant', 'user'])), 'Security event review recorded.');
    }

    public function export(Request $request, ExportAuditRequest $exportRequest): mixed
    {
        $filters = $exportRequest->validated();
        $query = $filters['source'] === 'activity_logs' ? ActivityLog::with(['actorPlatformUser', 'actorUser'])->latest('created_at') : SecurityEvent::with(['tenant', 'user'])->latest('created_at');
        $this->applyFilters($query, $filters, $filters['source']);
        $delivery = $filters['delivery'] ?? 'job';
        if ($delivery === 'download') {
            $rows = $query->limit(5001)->get();
            if ($rows->count() > 5000) return ApiResponse::error('Large audit exports must be queued.', 422, null, 'AUDIT_EXPORT_TOO_LARGE');
            ActivityLogger::record($request, 'audit.exported', $request->user(), 'Audit export downloaded.', ['source' => $filters['source'], 'filters' => $filters]);
            return response()->streamDownload(function () use ($rows, $filters): void { $this->writeCsv($rows, $filters['source']); }, 'audit-export.csv', ['Content-Type' => 'text/csv']);
        }
        $id = DB::table('report_export_jobs')->insertGetId(['uuid' => (string) Str::uuid(), 'report_code' => 'audit_'.$filters['source'], 'format' => 'csv', 'filters' => json_encode($filters), 'status' => 'queued', 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        ActivityLogger::record($request, 'audit.export_queued', $request->user(), 'Audit export queued.', ['job_id' => $id, 'filters' => $filters]);
        return ApiResponse::success(['job_id' => $id, 'status' => 'queued', 'format' => 'csv'], 'Audit export queued successfully.', 202);
    }

    private function paginate($query, Request $request, string $message): mixed { $page = $query->paginate($this->perPage($request))->withQueryString(); return ApiResponse::success($page->items(), $message, 200, $this->meta($page)); }
    private function perPage(Request $request): int { return min(max($request->integer('per_page', 25), 1), 100); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]; }
    private function sort($query, Request $request, array $allowed): void { $sort = in_array($request->get('sort'), $allowed, true) ? $request->get('sort') : 'created_at'; $query->orderBy($sort, $request->get('direction') === 'asc' ? 'asc' : 'desc'); }
    private function metadata(SecurityEvent $event): array { $metadata = $event->metadata; return is_array($metadata) ? $metadata : (json_decode((string) $metadata, true) ?: []); }
    private function presentSecurityEvent(SecurityEvent $event): array { $data = $event->toArray(); $data['metadata'] = $this->redact($this->metadata($event)); return $data; }
    private function redact(mixed $value): mixed { if (! is_array($value)) return $value; $out = []; foreach ($value as $key => $child) $out[$key] = preg_match('/(password|secret|token|authorization|credential|private[_-]?key|api[_-]?key)/i', (string) $key) ? '[REDACTED]' : $this->redact($child); return $out; }
    private function applyFilters($query, array $filters, string $source): void { if (isset($filters['tenant_id'])) $query->where('tenant_id', $filters['tenant_id']); if (isset($filters['event'])) $query->where('event', $filters['event']); if ($source === 'security_events' && isset($filters['severity'])) $query->where('severity', $filters['severity']); if ($source === 'security_events' && isset($filters['status'])) $query->whereJsonContains('metadata->review->status', $filters['status']); if (isset($filters['from'])) $query->where('created_at', '>=', $filters['from']); if (isset($filters['to'])) $query->where('created_at', '<=', $filters['to']); }
    private function writeCsv($rows, string $source): void { $out = fopen('php://output', 'wb'); fputcsv($out, $source === 'activity_logs' ? ['id', 'tenant_id', 'event', 'subject_type', 'subject_id', 'actor_platform_user_id', 'created_at', 'old_values', 'new_values'] : ['id', 'tenant_id', 'user_id', 'event', 'severity', 'ip_address', 'created_at', 'metadata']); foreach ($rows as $row) { $data = $source === 'activity_logs' ? [$row->id, $row->tenant_id, $row->event, $row->subject_type, $row->subject_id, $row->actor_platform_user_id, $row->created_at, json_encode($this->redact($row->old_values)), json_encode($this->redact($row->new_values))] : [$row->id, $row->tenant_id, $row->user_id, $row->event, $row->severity, $row->ip_address, $row->created_at, json_encode($this->redact($this->metadata($row)))]; fputcsv($out, array_map(fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value, $data)); } fclose($out); }
    public function platformActivityLogs(ListPlatformActivityLogsRequest $request): mixed
    {
        $query = ActivityLog::with(['actorPlatformUser', 'actorUser', 'tenant', 'subject'])->whereNull('tenant_id')->latest('created_at');
        foreach (['event', 'subject_type'] as $field) if ($request->filled($field)) $query->where($field, (string) $request->input($field));
        if ($request->filled('subject_id')) $query->where('subject_id', $request->integer('subject_id'));
        $page = $query->paginate($request->integer('per_page', 25))->withQueryString();
        return ApiResponse::success($page->items(), 'Activity logs fetched.', 200, ['current_page'=>$page->currentPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),'last_page'=>$page->lastPage()]);
    }

    public function compareActivity(int $activityId): mixed
    {
        $log = ActivityLog::with(['actorPlatformUser', 'actorUser', 'tenant', 'subject'])->whereNull('tenant_id')->find($activityId);
        if (! $log) return ApiResponse::error('Activity log not found.', 404, null, 'ACTIVITY_LOG_NOT_FOUND');
        $old = $log->old_values ?: []; $new = $log->new_values ?: []; $keys = array_unique(array_merge(array_keys($old), array_keys($new))); $changed=[];
        foreach ($keys as $key) if (($old[$key] ?? null) !== ($new[$key] ?? null)) $changed[$key]=['old'=>$old[$key]??null,'new'=>$new[$key]??null];
        return ApiResponse::success(['activity_log'=>$log,'comparison'=>['old_values'=>$old,'new_values'=>$new,'changed_fields'=>$changed]], 'Activity log comparison fetched.');
    }
}

