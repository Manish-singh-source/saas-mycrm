<?php
namespace App\Http\Controllers;

use App\Http\Requests\PlatformIntegrationRequest;
use App\Models\IntegrationFieldMapping;
use App\Models\IntegrationRateLimit;
use App\Models\IntegrationSyncJob;
use App\Models\IntegrationWebhook;
use App\Models\IntegrationWebhookLog;
use App\Models\IntegrationProvider;
use App\Models\Tenant;
use App\Models\TenantIntegration;
use App\Support\ApiResponse;
use App\Support\TenantAudit;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PlatformIntegrationController extends Controller
{
    public function index(PlatformIntegrationRequest $request): mixed
    {
        $query = TenantIntegration::with(['tenant:id,uuid,organization_name', 'provider:id,code,name'])->latest('id');
        if ($request->filled('tenant_uuid')) $query->whereHas('tenant', fn ($q) => $q->where('uuid', $request->validated('tenant_uuid')));
        $page = $query->paginate($request->validated('per_page', 25));
        $data = collect($page->items())->map(fn ($row) => $this->integration($row))->all();
        return ApiResponse::success($data, 'Tenant integrations fetched.', 200, $this->meta($page));
    }

    public function store(PlatformIntegrationRequest $request): mixed
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $tenant = Tenant::where('uuid', $data['tenant_uuid'])->first() ?? $this->missing('Tenant');
            $provider = IntegrationProvider::where('code', $data['provider_code'])->first() ?? $this->missing('Integration provider');
            $integration = TenantIntegration::create(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'provider_id' => $provider->id, 'name' => $data['name'], 'status' => $data['status'] ?? 'active', 'connected_by' => $request->user()->id, 'connected_at' => now()]);
            $stored = $this->storeCredentials($integration, $data['credentials'] ?? []);
            TenantAudit::record($request, $tenant, 'tenant_integration_created', ['integration_uuid' => $integration->uuid, 'provider_code' => $provider->code]);
            return ApiResponse::success(['integration' => $this->integration($integration->load(['tenant', 'provider'])), 'credentials' => ['stored' => $stored]], 'Tenant integration created.', 201);
        });
    }

    public function show(string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid)->load(['tenant', 'provider', 'credentials', 'webhooks', 'syncJobs']);
        return ApiResponse::success(['integration' => $this->integration($integration), 'credentials' => $integration->credentials->map(fn ($c) => ['key' => $c->key, 'expires_at' => $c->expires_at])->values(), 'webhooks' => $integration->webhooks->values(), 'sync_jobs' => $integration->syncJobs->sortByDesc('id')->values()], 'Tenant integration fetched.');
    }

    public function update(PlatformIntegrationRequest $request, string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid);
        $integration->update($request->validated());
        return ApiResponse::success(['integration' => $this->integration($integration->fresh()->load(['tenant', 'provider']))], 'Tenant integration updated.');
    }

    public function credentials(PlatformIntegrationRequest $request, string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid);
        $stored = $this->storeCredentials($integration, $request->validated('credentials', []));
        return ApiResponse::success(['credentials' => ['stored' => $stored]], 'Credentials rotated.');
    }

    public function test(string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid);
        return ApiResponse::success(['integration' => ['uuid' => $integration->uuid, 'status' => $integration->status], 'test' => ['status' => 'queued']], 'Integration test queued.');
    }

    public function disconnect(PlatformIntegrationRequest $request, string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid);
        $integration->update(['status' => 'disconnected']);
        TenantAudit::record($request, $integration->tenant, 'tenant_integration_disconnected', ['integration_uuid' => $integration->uuid]);
        return ApiResponse::success(['integration' => ['uuid' => $integration->uuid, 'status' => 'disconnected']], 'Integration disconnected.');
    }

    public function mappings(string $integration_uuid): mixed
    {
        return ApiResponse::success(['mappings' => $this->integrationModel($integration_uuid)->mappings], 'Mappings fetched.');
    }

    public function updateMappings(PlatformIntegrationRequest $request, string $integration_uuid): mixed
    {
        $integration = $this->integrationModel($integration_uuid);
        return DB::transaction(function () use ($request, $integration) {
            $integration->mappings()->delete();
            $rows = collect($request->validated('mappings', []))->map(fn ($row) => $row + ['tenant_integration_id' => $integration->id])->all();
            if ($rows !== []) IntegrationFieldMapping::insert($rows);
            return ApiResponse::success(['mappings' => $integration->mappings()->get()], 'Mappings updated.');
        });
    }

    public function rateLimits(string $integration_uuid): mixed
    {
        return ApiResponse::success(['rate_limits' => $this->integrationModel($integration_uuid)->rateLimits()->latest('window_end')->get()], 'Rate limits fetched.');
    }

    public function webhookIndex(PlatformIntegrationRequest $request): mixed
    {
        $page = IntegrationWebhook::with('integration')->latest('id')->paginate($request->validated('per_page', 25));
        return ApiResponse::success($page->items(), 'Webhooks fetched.', 200, $this->meta($page));
    }

    public function webhookStore(PlatformIntegrationRequest $request): mixed
    {
        $integration = $this->integrationModel($request->validated('integration_uuid'));
        $data = $request->validated();
        $webhook = $integration->webhooks()->create(['event' => $data['event'], 'secret_hash' => empty($data['secret']) ? null : Hash::make($data['secret']), 'status' => $data['status'] ?? 'active']);
        return ApiResponse::success(['webhook' => $webhook], 'Webhook created.', 201);
    }

    public function webhookShow(int $webhook_id): mixed
    {
        $webhook = IntegrationWebhook::with('logs')->find($webhook_id) ?? $this->missing('Webhook');
        return ApiResponse::success(['webhook' => $webhook->makeHidden('secret_hash'), 'logs' => $webhook->logs->sortByDesc('id')->take(50)->map(fn ($log) => $this->masked($log->toArray()))], 'Webhook fetched.');
    }

    public function webhookUpdate(PlatformIntegrationRequest $request, int $webhook_id): mixed
    {
        $webhook = IntegrationWebhook::find($webhook_id) ?? $this->missing('Webhook');
        $data = $request->validated();
        if (array_key_exists('secret', $data)) $data['secret_hash'] = empty($data['secret']) ? null : Hash::make($data['secret']);
        unset($data['secret']);
        $webhook->update($data);
        return ApiResponse::success(['webhook' => $webhook->fresh()->makeHidden('secret_hash')], 'Webhook updated.');
    }

    public function webhookDelete(int $webhook_id): mixed
    {
        $webhook = IntegrationWebhook::find($webhook_id) ?? $this->missing('Webhook');
        $webhook->update(['status' => 'inactive']);
        return ApiResponse::success(null, 'Webhook disabled.');
    }

    public function logs(PlatformIntegrationRequest $request, int $webhook_id): mixed
    {
        $page = IntegrationWebhookLog::where('webhook_id', $webhook_id)->latest('id')->paginate($request->validated('per_page', 25));
        return ApiResponse::success(collect($page->items())->map(fn ($log) => $this->masked($log->toArray()))->all(), 'Webhook logs fetched.', 200, $this->meta($page));
    }

    public function retryLog(int $log_id): mixed
    {
        $log = IntegrationWebhookLog::find($log_id) ?? $this->missing('Webhook log');
        $log->update(['status' => 'retry_queued']);
        return ApiResponse::success(['log' => $log->fresh()], 'Webhook retry queued.');
    }

    public function syncJobs(PlatformIntegrationRequest $request): mixed
    {
        $page = IntegrationSyncJob::with('integration')->latest('id')->paginate($request->validated('per_page', 25));
        return ApiResponse::success($page->items(), 'Sync jobs fetched.', 200, $this->meta($page));
    }

    public function retryJob(int $job_id): mixed
    {
        $job = IntegrationSyncJob::find($job_id) ?? $this->missing('Sync job');
        $job->update(['status' => 'retry_queued']);
        return ApiResponse::success(['job' => $job->fresh()], 'Sync retry queued.');
    }

    private function integrationModel(string $uuid): TenantIntegration
    {
        return TenantIntegration::with('tenant')->where('uuid', $uuid)->first() ?? $this->missing('Tenant integration');
    }

    private function integration(TenantIntegration $row): array
    {
        return ['id' => $row->id, 'uuid' => $row->uuid, 'tenant_id' => $row->tenant_id, 'provider_id' => $row->provider_id, 'tenant_name' => $row->tenant?->organization_name, 'provider_code' => $row->provider?->code, 'provider_name' => $row->provider?->name, 'name' => $row->name, 'status' => $row->status, 'connected_at' => $row->connected_at, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at];
    }

    private function storeCredentials(TenantIntegration $integration, array $credentials): array
    {
        foreach ($credentials as $key => $value) $integration->credentials()->updateOrCreate(['key' => $key], ['encrypted_value' => Crypt::encryptString((string) $value)]);
        return array_keys($credentials);
    }

    private function meta($page): array { return ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]; }
    private function masked(array $value): array
    {
        foreach ($value as $key => $item) {
            if (in_array(strtolower((string) $key), ['secret', 'secret_hash', 'token', 'authorization', 'password'], true)) $value[$key] = '***masked***';
            elseif (is_array($item)) $value[$key] = $this->masked($item);
        }
        return $value;
    }
    private function missing(string $name): never { throw new HttpResponseException(ApiResponse::error($name.' not found.', 404)); }
}