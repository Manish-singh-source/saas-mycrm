<?php

namespace App\Http\Controllers\Platform;

use App\Services\Platform\PlatformOperationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformIntegrationController extends BasePlatformController
{
    public function __construct(private readonly PlatformOperationsService $ops) {}

    public function providers(Request $request)
    {
        $p = DB::table('integration_providers')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeProvider(Request $request)
    {
        $d = $request->validate(['name' => ['required', 'string'], 'code' => ['required', 'string'], 'category' => ['required', 'string'], 'auth_type' => ['required', 'string'], 'status' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
        $id = DB::table('integration_providers')->insertGetId([...collect($d)->except('metadata')->all(), 'metadata' => isset($d['metadata']) ? json_encode($this->ops->mask($d['metadata'])) : null, 'status' => $d['status'] ?? 'active', 'created_at' => now(), 'updated_at' => now()]);
        return $this->success(['provider' => DB::table('integration_providers')->where('id', $id)->first()], 'Provider created.', 201);
    }
    public function updateProvider(Request $request, string $provider_code)
    {
        $p = DB::table('integration_providers')->where('code', $provider_code)->first();
        abort_if(! $p, 404);
        $d = $request->validate(['name' => ['sometimes', 'string'], 'category' => ['nullable', 'string'], 'auth_type' => ['nullable', 'string'], 'status' => ['nullable', 'string'], 'metadata' => ['nullable', 'array']]);
        if (isset($d['metadata'])) $d['metadata'] = json_encode($this->ops->mask($d['metadata']));
        DB::table('integration_providers')->where('id', $p->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['provider' => DB::table('integration_providers')->where('id', $p->id)->first()], 'Provider updated.');
    }

    public function tenantIntegrations(Request $request)
    {
        $q = DB::table('tenant_integrations');
        if ($request->filled('tenant_uuid')) $q->where('tenant_id', $this->ops->tenantId($request->input('tenant_uuid')));
        $p = $q->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeTenantIntegration(Request $request)
    {
        $d = $request->validate(['tenant_uuid' => ['required', 'uuid'], 'provider_code' => ['required', 'string'], 'name' => ['required', 'string'], 'status' => ['nullable', 'string'], 'credentials' => ['nullable', 'array']]);
        $provider = DB::table('integration_providers')->where('code', $d['provider_code'])->first();
        abort_if(! $provider, 404);
        return DB::transaction(function () use ($request, $d, $provider) {
            $id = DB::table('tenant_integrations')->insertGetId(['uuid' => (string) Str::uuid(), 'tenant_id' => $this->ops->tenantId($d['tenant_uuid']), 'provider_id' => $provider->id, 'name' => $d['name'], 'status' => $d['status'] ?? 'active', 'connected_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($this->ops->encryptCredentials($d['credentials'] ?? []) as $row) DB::table('integration_credentials')->updateOrInsert(['tenant_integration_id' => $id, 'key' => $row['key']], ['encrypted_value' => $row['encrypted_value'], 'expires_at' => $row['expires_at']]);
            $integration = DB::table('tenant_integrations')->where('id', $id)->first();
            $this->ops->audit($request, 'tenant_integration_created', 'tenant_integrations', $id, null, (array) $integration);
            return $this->success(['integration' => $integration, 'credentials' => ['stored' => array_keys($d['credentials'] ?? [])]], 'Tenant integration created.', 201);
        });
    }
    public function showTenantIntegration(string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        return $this->success(['integration' => $i, 'credentials' => DB::table('integration_credentials')->where('tenant_integration_id', $i->id)->get(['key', 'expires_at']), 'webhooks' => DB::table('integration_webhooks')->where('tenant_integration_id', $i->id)->get(), 'sync_jobs' => DB::table('integration_sync_jobs')->where('tenant_integration_id', $i->id)->latest('id')->get()]);
    }
    public function updateTenantIntegration(Request $request, string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        $d = $request->validate(['name' => ['sometimes', 'string'], 'status' => ['nullable', 'string']]);
        DB::table('tenant_integrations')->where('id', $i->id)->update([...$d, 'updated_at' => now()]);
        return $this->success(['integration' => DB::table('tenant_integrations')->where('id', $i->id)->first()], 'Tenant integration updated.');
    }
    public function rotateCredentials(Request $request, string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        $d = $request->validate(['credentials' => ['required', 'array']]);
        foreach ($this->ops->encryptCredentials($d['credentials']) as $row) DB::table('integration_credentials')->updateOrInsert(['tenant_integration_id' => $i->id, 'key' => $row['key']], ['encrypted_value' => $row['encrypted_value'], 'expires_at' => $row['expires_at']]);
        $this->ops->audit($request, 'integration_credentials_rotated', 'tenant_integrations', $i->id, null, ['keys' => array_keys($d['credentials'])]);
        return $this->success(['credentials' => ['stored' => array_keys($d['credentials'])]], 'Credentials rotated.');
    }
    public function testIntegration(string $integration_uuid)
    {
        return $this->success(['integration' => $this->ops->byUuid('tenant_integrations', $integration_uuid), 'test' => ['status' => 'queued']], 'Integration test queued.');
    }
    public function disconnectIntegration(Request $request, string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        DB::table('tenant_integrations')->where('id', $i->id)->update(['status' => 'disconnected', 'updated_at' => now()]);
        $this->ops->audit($request, 'tenant_integration_disconnected', 'tenant_integrations', $i->id, (array) $i, ['status' => 'disconnected']);
        return $this->success(['integration' => DB::table('tenant_integrations')->where('id', $i->id)->first()], 'Integration disconnected.');
    }

    public function webhooks(Request $request)
    {
        $p = DB::table('integration_webhooks')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function storeWebhook(Request $request)
    {
        $d = $request->validate(['integration_uuid' => ['required', 'uuid'], 'event' => ['required', 'string'], 'secret' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        $i = $this->ops->byUuid('tenant_integrations', $d['integration_uuid']);
        $id = DB::table('integration_webhooks')->insertGetId(['tenant_integration_id' => $i->id, 'event' => $d['event'], 'secret_hash' => isset($d['secret']) ? Hash::make($d['secret']) : null, 'status' => $d['status'] ?? 'active']);
        return $this->success(['webhook' => DB::table('integration_webhooks')->where('id', $id)->first()], 'Webhook created.', 201);
    }
    public function showWebhook(int $webhook_id)
    {
        $w = DB::table('integration_webhooks')->where('id', $webhook_id)->first();
        abort_if(! $w, 404);
        return $this->success(['webhook' => $w, 'logs' => DB::table('integration_webhook_logs')->where('webhook_id', $w->id)->latest('id')->limit(50)->get()->map(fn($l) => tap($l, fn($x) => $x->payload = $this->ops->mask($x->payload)))]);
    }
    public function updateWebhook(Request $request, int $webhook_id)
    {
        $w = DB::table('integration_webhooks')->where('id', $webhook_id)->first();
        abort_if(! $w, 404);
        $d = $request->validate(['event' => ['nullable', 'string'], 'secret' => ['nullable', 'string'], 'status' => ['nullable', 'string']]);
        if (isset($d['secret'])) {
            $d['secret_hash'] = Hash::make($d['secret']);
            unset($d['secret']);
        }
        DB::table('integration_webhooks')->where('id', $w->id)->update($d);
        return $this->success(['webhook' => DB::table('integration_webhooks')->where('id', $w->id)->first()], 'Webhook updated.');
    }
    public function deleteWebhook(int $webhook_id)
    {
        DB::table('integration_webhooks')->where('id', $webhook_id)->update(['status' => 'inactive']);
        return $this->success(null, 'Webhook disabled.');
    }
    public function webhookLogs(Request $request, int $webhook_id)
    {
        $p = DB::table('integration_webhook_logs')->where('webhook_id', $webhook_id)->latest('id')->paginate((int) $request->integer('per_page', 25));
        $items = collect($p->items())->map(fn($l) => tap($l, fn($x) => $x->payload = $this->ops->mask($x->payload)))->all();
        return $this->list($items, $p);
    }
    public function retryWebhookLog(Request $request, int $log_id)
    {
        $this->ops->retryOnce($request, 'integration_webhook_logs', $log_id, 'integration_webhook_retry_queued');
        return $this->success(['log' => DB::table('integration_webhook_logs')->where('id', $log_id)->first()], 'Webhook retry queued.');
    }

    public function syncJobs(Request $request)
    {
        $p = DB::table('integration_sync_jobs')->latest('id')->paginate((int) $request->integer('per_page', 25));
        return $this->list($p->items(), $p);
    }
    public function retrySyncJob(Request $request, int $job_id)
    {
        $job = DB::table('integration_sync_jobs')->where('id', $job_id)->first();
        abort_if(! $job, 404);
        DB::table('integration_sync_jobs')->where('id', $job_id)->update(['status' => 'retry_queued']);
        $this->ops->audit($request, 'integration_sync_retry_queued', 'integration_sync_jobs', $job_id, (array) $job, ['status' => 'retry_queued']);
        return $this->success(['job' => DB::table('integration_sync_jobs')->where('id', $job_id)->first()], 'Sync retry queued.');
    }
    public function mappings(Request $request, string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        return $this->success(['mappings' => DB::table('integration_field_mappings')->where('tenant_integration_id', $i->id)->get()]);
    }
    public function replaceMappings(Request $request, string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        $d = $request->validate(['mappings' => ['required', 'array'], 'mappings.*.entity_type' => ['required', 'string'], 'mappings.*.local_field' => ['required', 'string'], 'mappings.*.external_field' => ['required', 'string'], 'mappings.*.transform_rule' => ['nullable', 'array']]);
        DB::table('integration_field_mappings')->where('tenant_integration_id', $i->id)->delete();
        foreach ($d['mappings'] as $m) DB::table('integration_field_mappings')->insert(['tenant_integration_id' => $i->id, 'entity_type' => $m['entity_type'], 'local_field' => $m['local_field'], 'external_field' => $m['external_field'], 'transform_rule' => isset($m['transform_rule']) ? json_encode($m['transform_rule']) : null]);
        return $this->success(['mappings' => DB::table('integration_field_mappings')->where('tenant_integration_id', $i->id)->get()], 'Mappings updated.');
    }
    public function rateLimits(string $integration_uuid)
    {
        $i = $this->ops->byUuid('tenant_integrations', $integration_uuid);
        return $this->success(['rate_limits' => DB::table('integration_rate_limits')->where('tenant_integration_id', $i->id)->latest('id')->get()]);
    }
}
