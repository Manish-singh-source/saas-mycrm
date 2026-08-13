<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformSettingsAuditController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops) {}

    public function settings(Request $request)
    {
        $q = DB::table('platform_settings');
        if ($request->filled('group')) $q->where('group', $request->input('group'));
        return $this->success(['settings' => $q->orderBy('group')->orderBy('key')->get()]);
    }
    public function updateSettings(Request $request)
    {
        $d = $request->validate(['settings' => ['required', 'array']]);
        foreach ($d['settings'] as $group => $values) foreach ((array) $values as $key => $value) DB::table('platform_settings')->updateOrInsert(['group' => $group, 'key' => $key], ['value' => json_encode($value), 'value_type' => gettype($value), 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->ops->audit($request, 'platform_settings_updated', 'platform_settings', $request->user()?->id ?? 0, null, $d);
        return $this->settings($request);
    }

    public function templates(Request $request)
    {
        $p = DB::table('notification_templates')->whereNull('tenant_id')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeTemplate(Request $request)
    {
        $d = $request->validate(['code' => ['required', 'string'], 'channel' => ['required', 'string'], 'subject' => ['nullable', 'string'], 'body' => ['required', 'string'], 'variables' => ['nullable', 'array'], 'status' => ['nullable', 'string']]);
        $id = DB::table('notification_templates')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => null, ...collect($d)->except('variables')->all(), 'variables' => isset($d['variables']) ? json_encode($d['variables']) : null, 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['template' => DB::table('notification_templates')->where('id', $id)->first()], 'Template created.', 201);
    }
    public function updateTemplate(Request $request, string $template_uuid)
    {
        $t = $this->ops->byUuid('notification_templates', $template_uuid);
        $d = $request->validate(['subject' => ['nullable', 'string'], 'body' => ['nullable', 'string'], 'variables' => ['nullable', 'array'], 'status' => ['nullable', 'string']]);
        if (isset($d['variables'])) $d['variables'] = json_encode($d['variables']);
        DB::table('notification_templates')->where('id', $t->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['template' => DB::table('notification_templates')->where('id', $t->id)->first()], 'Template updated.');
    }

    public function backupSettings()
    {
        return $this->success(['settings' => DB::table('backup_settings')->orderBy('key')->get(), 'last_run' => DB::table('backup_runs')->latest('id')->first()]);
    }
    public function updateBackupSettings(Request $request)
    {
        $d = $request->validate(['settings' => ['required', 'array']]);
        foreach ($d['settings'] as $key => $value) DB::table('backup_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->backupSettings();
    }
    public function runBackup(Request $request)
    {
        $d = $request->validate(['backup_type' => ['nullable', 'string']]);
        $id = DB::table('backup_runs')->insertGetId(['uuid' => (string) Str::uuid(), 'backup_type' => $d['backup_type'] ?? 'manual', 'status' => 'queued', 'started_at' => now()]);
        $this->ops->audit($request, 'backup_run_queued', 'backup_runs', $id, null, $d);
        return $this->success(['backup_run' => DB::table('backup_runs')->where('id', $id)->first()], 'Backup queued.', 201);
    }
    public function backupRuns(Request $request)
    {
        $p = DB::table('backup_runs')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function backupRun(string $run_uuid)
    {
        return $this->success(['backup_run' => $this->ops->byUuid('backup_runs', $run_uuid)]);
    }
    public function backupDownload(string $run_uuid)
    {
        $run = $this->ops->byUuid('backup_runs', $run_uuid);
        return $this->success(['backup_run' => $run, 'download' => $run->file_id ? ['file_id' => $run->file_id] : null]);
    }

    public function activityLogs(Request $request)
    {
        $query = $this->auditQuery($request);
        $this->applyAuditSort($query, $request);
        $p = $query->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function securityEvents(Request $request)
    {
        $p = DB::table('security_events')->latest('id')->paginate((int) $request->integer('per_page', 25));
        $items = collect($p->items())->map(fn($e) => tap($e, fn($x) => $x->metadata = $this->ops->mask($x->metadata)))->all();
        return $this->list($items, $p);
    }
    public function reviewSecurityEvent(Request $request, int $event_id)
    {
        $e = DB::table('security_events')->where('id', $event_id)->first();
        abort_if(! $e, 404);
        $d = $request->validate(['status' => ['required', 'string'], 'notes' => ['required', 'string']]);
        $metadata = $this->ops->mask($e->metadata);
        $metadata['review'] = ['status' => $d['status'], 'notes' => $d['notes'], 'reviewed_by' => $request->user()?->id, 'reviewed_at' => now()->toISOString()];
        DB::table('security_events')->where('id', $event_id)->update(['metadata' => json_encode($metadata)]);
        $this->ops->audit($request, 'security_event_reviewed', 'security_events', $event_id, (array) $e, $metadata, $d['notes']);
        return $this->success(['security_event' => DB::table('security_events')->where('id', $event_id)->first()], 'Security event reviewed.');
    }
    public function exportAudit(Request $request)
    {
        $data = $this->auditExportData($request);
        if ($data['delivery'] === 'download') {
            $query = $this->auditQuery($request);
            $this->applyAuditSort($query, $request);
            $records = $query->limit(5000)->get()->map(fn($record) => (array) $record)->all();
            $content = $this->csv($records, $data['columns']);
            return $this->success(['download' => ['filename' => 'audit-logs-' . now()->format('Ymd-His') . '.csv', 'mime_type' => 'text/csv', 'size_bytes' => strlen($content), 'content' => $content]], 'Audit export ready.', 201);
        }
        $id = DB::table('report_export_jobs')->insertGetId(['uuid' => (string) Str::uuid(), 'report_code' => 'audit-logs', 'format' => $data['format'], 'filters' => json_encode([...$request->query(), ...$data]), 'status' => 'queued', 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['export' => DB::table('report_export_jobs')->where('id', $id)->first()], 'Audit export queued.', 201);
    }

    public function onboardingTenants(Request $request)
    {
        $p = DB::table('tenants')->leftJoin('tenant_onboarding_steps', 'tenant_onboarding_steps.tenant_id', '=', 'tenants.id')->selectRaw('tenants.uuid, tenants.organization_name, tenants.status, COUNT(tenant_onboarding_steps.id) as steps')->groupBy('tenants.uuid', 'tenants.organization_name', 'tenants.status')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function onboardingTenant(string $tenant_uuid)
    {
        $tenantId = $this->ops->tenantId($tenant_uuid);
        return $this->success(['tenant' => DB::table('tenants')->where('id', $tenantId)->first(), 'steps' => DB::table('tenant_onboarding_steps')->where('tenant_id', $tenantId)->get()]);
    }
    public function updateOnboardingStep(Request $request, string $tenant_uuid, string $step_code)
    {
        $tenantId = $this->ops->tenantId($tenant_uuid);
        $d = $request->validate(['status' => ['required', 'string'], 'metadata' => ['nullable', 'array']]);
        DB::table('tenant_onboarding_steps')->updateOrInsert(['tenant_id' => $tenantId, 'step_code' => $step_code], ['status' => $d['status'], 'metadata' => isset($d['metadata']) ? json_encode($d['metadata']) : null, 'updated_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['step' => DB::table('tenant_onboarding_steps')->where('tenant_id', $tenantId)->where('step_code', $step_code)->first()], 'Onboarding step updated.');
    }
    public function trials(Request $request)
    {
        $p = DB::table('tenants')->where('status', 'trial')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function extendTrial(Request $request, string $tenant_uuid)
    {
        return app(PlatformTenantController::class)->extendTrial($request, $tenant_uuid);
    }
    public function convertTrial(Request $request, string $tenant_uuid)
    {
        $tenantId = $this->ops->tenantId($tenant_uuid);
        DB::table('tenants')->where('id', $tenantId)->update(['status' => 'active', 'updated_at' => now()]);
        DB::table('subscriptions')->where('tenant_id', $tenantId)->latest('id')->limit(1)->update(['status' => 'active', 'updated_at' => now()]);
        return $this->success(['tenant' => DB::table('tenants')->where('id', $tenantId)->first()], 'Trial converted.');
    }

    public function legalDocuments(Request $request)
    {
        $p = DB::table('legal_documents')->whereNull('deleted_at')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeLegalDocument(Request $request)
    {
        $d = $request->validate(['document_type' => ['required', 'string'], 'title' => ['required', 'string'], 'version' => ['required', 'string'], 'content' => ['required', 'string'], 'status' => ['nullable', 'string']]);
        $id = DB::table('legal_documents')->insertGetId(['uuid' => (string) Str::uuid(), ...$d, 'status' => $d['status'] ?? 'draft', 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['document' => DB::table('legal_documents')->where('id', $id)->first()], 'Legal document created.', 201);
    }
    public function legalDocument(string $uuid)
    {
        $doc = $this->ops->byUuid('legal_documents', $uuid);
        return $this->success(['document' => $doc, 'acceptances' => DB::table('tenant_legal_acceptances')->where('legal_document_id', $doc->id)->latest('id')->limit(50)->get()]);
    }
    public function updateLegalDocument(Request $request, string $uuid)
    {
        $doc = $this->ops->byUuid('legal_documents', $uuid);
        $d = $request->validate(['title' => ['nullable', 'string'], 'content' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        DB::table('legal_documents')->where('id', $doc->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['document' => DB::table('legal_documents')->where('id', $doc->id)->first()], 'Legal document updated.');
    }
    public function publishLegalDocument(Request $request, string $uuid)
    {
        $doc = $this->ops->byUuid('legal_documents', $uuid);
        DB::table('legal_documents')->where('id', $doc->id)->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);
        return $this->success(['document' => DB::table('legal_documents')->where('id', $doc->id)->first()], 'Legal document published.');
    }
    public function legalAcceptances(string $uuid)
    {
        $doc = $this->ops->byUuid('legal_documents', $uuid);
        return $this->success(['acceptances' => DB::table('tenant_legal_acceptances')->where('legal_document_id', $doc->id)->get()]);
    }

    public function announcements(Request $request)
    {
        $p = DB::table('platform_announcements')->whereNull('deleted_at')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeAnnouncement(Request $request)
    {
        $d = $request->validate(['title' => ['required', 'string'], 'body' => ['required', 'string'], 'audience' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        $id = DB::table('platform_announcements')->insertGetId(['uuid' => (string) Str::uuid(), ...$d, 'audience' => $d['audience'] ?? 'all', 'status' => $d['status'] ?? 'draft', 'created_by' => $request->user()?->id, 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['announcement' => DB::table('platform_announcements')->where('id', $id)->first()], 'Announcement created.', 201);
    }
    public function announcement(string $uuid)
    {
        return $this->success(['announcement' => $this->ops->byUuid('platform_announcements', $uuid)]);
    }
    public function updateAnnouncement(Request $request, string $uuid)
    {
        $a = $this->ops->byUuid('platform_announcements', $uuid);
        $d = $request->validate(['title' => ['nullable', 'string'], 'body' => ['nullable', 'string'], 'audience' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        DB::table('platform_announcements')->where('id', $a->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['announcement' => DB::table('platform_announcements')->where('id', $a->id)->first()], 'Announcement updated.');
    }
    public function publishAnnouncement(string $uuid)
    {
        $a = $this->ops->byUuid('platform_announcements', $uuid);
        DB::table('platform_announcements')->where('id', $a->id)->update(['status' => 'published', 'published_at' => now(), 'updated_at' => now()]);
        return $this->success(['announcement' => DB::table('platform_announcements')->where('id', $a->id)->first()], 'Announcement published.');
    }
    public function archiveAnnouncement(string $uuid)
    {
        $a = $this->ops->byUuid('platform_announcements', $uuid);
        DB::table('platform_announcements')->where('id', $a->id)->update(['status' => 'archived', 'deleted_at' => now(), 'updated_at' => now()]);
        return $this->success(null, 'Announcement archived.');
    }
    public function deleteAnnouncement(string $uuid)
    {
        return $this->archiveAnnouncement($uuid);
    }

    public function webhookEndpoints(Request $request)
    {
        $p = DB::table('platform_webhook_endpoints')->whereNull('deleted_at')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeWebhookEndpoint(Request $request)
    {
        $d = $request->validate(['tenant_uuid' => ['nullable', 'uuid'], 'name' => ['required', 'string'], 'url' => ['required', 'url'], 'events' => ['nullable', 'array'], 'secret' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        $id = DB::table('platform_webhook_endpoints')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->ops->tenantId($d['tenant_uuid'] ?? null), 'name' => $d['name'], 'url' => $d['url'], 'events' => isset($d['events']) ? json_encode($d['events']) : null, 'secret_hash' => isset($d['secret']) ? Hash::make($d['secret']) : null, 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['endpoint' => DB::table('platform_webhook_endpoints')->where('id', $id)->first()], 'Webhook endpoint created.', 201);
    }
    public function webhookEndpoint(string $uuid)
    {
        $e = $this->ops->byUuid('platform_webhook_endpoints', $uuid);
        return $this->success(['endpoint' => $e, 'deliveries' => DB::table('platform_webhook_deliveries')->where('platform_webhook_endpoint_id', $e->id)->latest('id')->limit(50)->get()->map(fn($d) => tap($d, fn($x) => $x->payload = $this->ops->mask($x->payload)))]);
    }
    public function updateWebhookEndpoint(Request $request, string $uuid)
    {
        $e = $this->ops->byUuid('platform_webhook_endpoints', $uuid);
        $d = $request->validate(['name' => ['nullable', 'string'], 'url' => ['nullable', 'url'], 'events' => ['nullable', 'array'], 'secret' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        if (isset($d['events'])) $d['events'] = json_encode($d['events']);
        if (isset($d['secret'])) {
            $d['secret_hash'] = Hash::make($d['secret']);
            unset($d['secret']);
        }
        DB::table('platform_webhook_endpoints')->where('id', $e->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['endpoint' => DB::table('platform_webhook_endpoints')->where('id', $e->id)->first()], 'Webhook endpoint updated.');
    }
    public function deleteWebhookEndpoint(string $uuid)
    {
        $e = $this->ops->byUuid('platform_webhook_endpoints', $uuid);
        DB::table('platform_webhook_endpoints')->where('id', $e->id)->update(['status' => 'inactive', 'deleted_at' => now(), 'updated_at' => now()]);
        return $this->success(null, 'Webhook endpoint deleted.');
    }
    public function webhookDeliveries(string $uuid)
    {
        $e = $this->ops->byUuid('platform_webhook_endpoints', $uuid);
        return $this->success(['deliveries' => DB::table('platform_webhook_deliveries')->where('platform_webhook_endpoint_id', $e->id)->get()->map(fn($d) => tap($d, fn($x) => $x->payload = $this->ops->mask($x->payload)))]);
    }
    public function webhookDelivery(string $uuid)
    {
        $d = $this->ops->byUuid('platform_webhook_deliveries', $uuid);
        $d->payload = $this->ops->mask($d->payload);
        return $this->success(['delivery' => $d]);
    }
    public function retryWebhookDelivery(Request $request, string $uuid)
    {
        $d = $this->ops->byUuid('platform_webhook_deliveries', $uuid);
        DB::table('platform_webhook_deliveries')->where('id', $d->id)->update(['status' => 'retry_queued', 'retry_count' => DB::raw('retry_count + 1'), 'queued_at' => now(), 'updated_at' => now()]);
        return $this->success(['delivery' => DB::table('platform_webhook_deliveries')->where('id', $d->id)->first()], 'Webhook delivery retry queued.');
    }
    private function auditQuery(Request $request): Builder
    {
        $query = DB::table('activity_logs');
        $value = fn(string $key) => $request->input('filter.' . $key, $request->input('filters.' . $key, $request->input($key)));

        if ($search = $value('search')) {
            $query->where(fn($q) => $q->where('event', 'like', '%' . $search . '%')->orWhere('subject_type', 'like', '%' . $search . '%')->orWhere('description', 'like', '%' . $search . '%'));
        }
        foreach (['event', 'subject_type', 'subject_id', 'actor_platform_user_id'] as $field) {
            if (($filterValue = $value($field)) !== null && $filterValue !== '') {
                $query->where($field, $filterValue);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        $selected = $request->input('selected_ids', []);
        if ($request->input('scope') === 'selected' && is_array($selected) && $selected !== []) {
            $query->whereIn('id', $selected);
        }

        return $query;
    }

    private function applyAuditSort(Builder $query, Request $request): void
    {
        $allowed = ['id', 'event', 'subject_type', 'created_at'];
        $sort = (string) $request->input('sort', 'created_at');
        if (! in_array($sort, $allowed, true)) {
            $sort = 'created_at';
        }
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }

    private function auditExportData(Request $request): array
    {
        $columns = ['id', 'actor_platform_user_id', 'subject_type', 'subject_id', 'event', 'description', 'ip_address', 'request_id', 'created_at'];
        $data = $request->validate([
            'format' => ['nullable', Rule::in(['csv'])],
            'delivery' => ['nullable', Rule::in(['job', 'download'])],
            'scope' => ['nullable', Rule::in(['filtered', 'selected'])],
            'filters' => ['nullable', 'array'],
            'sort' => ['nullable', Rule::in(['id', 'event', 'subject_type', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['string'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'email_when_ready' => ['nullable', 'boolean'],
        ]);
        $requested = array_values(array_intersect($data['columns'] ?? [], $columns));

        return [
            ...$data,
            'format' => $data['format'] ?? 'csv',
            'delivery' => $data['delivery'] ?? 'job',
            'scope' => $data['scope'] ?? 'filtered',
            'columns' => $requested !== [] ? $requested : $columns,
        ];
    }

    private function csv(array $records, array $columns): string
    {
        $lines = [implode(',', $columns)];
        foreach ($records as $record) {
            $lines[] = implode(',', array_map(fn($column) => $this->csvValue($record[$column] ?? null), $columns));
        }
        return implode("\n", $lines) . "\n";
    }

    private function csvValue(mixed $value): string
    {
        if ($value === null) $value = '';
        if (is_array($value) || is_object($value)) $value = json_encode($value);
        return '"' . str_replace('"', '""', (string) $value) . '"';
    }
}
