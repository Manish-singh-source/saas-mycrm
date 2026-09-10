<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformWebhookEndpointRequest;
use App\Http\Requests\UpdatePlatformWebhookEndpointRequest;
use App\Models\PlatformWebhookDelivery;
use App\Models\PlatformWebhookEndpoint;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformWebhookController extends Controller
{
    public function endpoints(Request $request): mixed
    {
        $query = PlatformWebhookEndpoint::with('tenant')->latest('created_at');
        if ($request->filled('tenant_id')) $query->where('tenant_id', $request->integer('tenant_id'));
        if ($request->filled('status')) $query->where('status', (string) $request->string('status'));
        if ($request->filled('search')) { $search = (string) $request->string('search'); $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('url', 'like', "%{$search}%")); }
        $page = $query->paginate($this->perPage($request))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn ($endpoint) => $this->presentEndpoint($endpoint))->values(), 'Webhook endpoints fetched.', 200, $this->meta($page));
    }

    public function store(StorePlatformWebhookEndpointRequest $request): mixed
    {
        $data = $request->validated();
        $tenantId = isset($data['tenant_uuid']) ? Tenant::where('uuid', $data['tenant_uuid'])->value('id') : null;
        $endpoint = new PlatformWebhookEndpoint();
        $endpoint->forceFill(['uuid' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'name' => $data['name'], 'url' => $data['url'], 'events' => $data['events'], 'secret_hash' => isset($data['secret']) ? hash('sha256', $data['secret']) : null, 'status' => $data['status'] ?? 'active'])->save();
        ActivityLogger::record($request, 'webhook_endpoint.created', $endpoint, 'Webhook endpoint created.', ['uuid' => $endpoint->uuid, 'tenant_id' => $tenantId, 'events' => $endpoint->events]);
        return ApiResponse::success(['endpoint' => $this->load($endpoint)], 'Webhook endpoint created successfully.', 201);
    }

    public function show(string $uuid): mixed
    {
        $endpoint = PlatformWebhookEndpoint::with(['tenant', 'deliveries' => fn ($q) => $q->latest('created_at')->limit(25)])->where('uuid', $uuid)->first();
        if (! $endpoint) return ApiResponse::error('Webhook endpoint not found.', 404, null, 'WEBHOOK_ENDPOINT_NOT_FOUND');
        $endpoint->setRelation('deliveries', $endpoint->deliveries->map(fn ($delivery) => $this->presentDelivery($delivery)));
        return ApiResponse::success(['endpoint' => $this->presentEndpoint($endpoint)], 'Webhook endpoint fetched.');
    }

    public function update(UpdatePlatformWebhookEndpointRequest $request, string $uuid): mixed
    {
        $endpoint = PlatformWebhookEndpoint::where('uuid', $uuid)->first();
        if (! $endpoint) return ApiResponse::error('Webhook endpoint not found.', 404, null, 'WEBHOOK_ENDPOINT_NOT_FOUND');
        $data = $request->validated(); $old = $this->auditData($endpoint);
        $changes = collect($data)->except(['secret', 'tenant_uuid'])->all();
        if (array_key_exists('tenant_uuid', $data)) $changes['tenant_id'] = $data['tenant_uuid'] ? Tenant::where('uuid', $data['tenant_uuid'])->value('id') : null;
        if (array_key_exists('secret', $data)) $changes['secret_hash'] = $data['secret'] ? hash('sha256', $data['secret']) : null;
        $endpoint->forceFill($changes)->save();
        ActivityLogger::record($request, 'webhook_endpoint.updated', $endpoint, 'Webhook endpoint updated.', ['old' => $old, 'new' => $this->auditData($endpoint)]);
        return ApiResponse::success(['endpoint' => $this->load($endpoint->fresh())], 'Webhook endpoint updated successfully.');
    }

    public function destroy(Request $request, string $uuid): mixed
    {
        $endpoint = PlatformWebhookEndpoint::where('uuid', $uuid)->first();
        if (! $endpoint) return ApiResponse::error('Webhook endpoint not found.', 404, null, 'WEBHOOK_ENDPOINT_NOT_FOUND');
        if ($endpoint->status !== 'inactive' || ! $endpoint->trashed()) { $endpoint->forceFill(['status' => 'inactive'])->save(); $endpoint->delete(); ActivityLogger::record($request, 'webhook_endpoint.disabled', $endpoint, 'Webhook endpoint disabled.'); }
        return ApiResponse::success(null, 'Webhook endpoint deleted successfully.');
    }

    public function deliveries(Request $request, string $uuid): mixed
    {
        $endpoint = PlatformWebhookEndpoint::where('uuid', $uuid)->first();
        if (! $endpoint) return ApiResponse::error('Webhook endpoint not found.', 404, null, 'WEBHOOK_ENDPOINT_NOT_FOUND');
        $query = $endpoint->deliveries()->with('endpoint')->latest('created_at');
        foreach (['status', 'event'] as $field) if ($request->filled($field)) $query->where($field, (string) $request->string($field));
        $page = $query->paginate($this->perPage($request))->withQueryString();
        return ApiResponse::success(collect($page->items())->map(fn ($delivery) => $this->presentDelivery($delivery))->values(), 'Webhook deliveries fetched.', 200, $this->meta($page));
    }

    public function delivery(string $uuid): mixed
    {
        $delivery = PlatformWebhookDelivery::with('endpoint.tenant')->where('uuid', $uuid)->first();
        if (! $delivery) return ApiResponse::error('Webhook delivery not found.', 404, null, 'WEBHOOK_DELIVERY_NOT_FOUND');
        return ApiResponse::success(['delivery' => $this->presentDelivery($delivery)], 'Webhook delivery fetched.');
    }

    public function retry(Request $request, string $uuid): mixed
    {
        $delivery = PlatformWebhookDelivery::with('endpoint')->where('uuid', $uuid)->first();
        if (! $delivery) return ApiResponse::error('Webhook delivery not found.', 404, null, 'WEBHOOK_DELIVERY_NOT_FOUND');
        if ($delivery->status === 'retry_queued') return ApiResponse::success(['delivery' => $this->presentDelivery($delivery)], 'Webhook delivery retry is already queued.');
        if (in_array($delivery->status, ['delivered', 'succeeded'], true)) return ApiResponse::success(['delivery' => $this->presentDelivery($delivery)], 'Webhook delivery was already delivered.');
        if ($delivery->endpoint?->status !== 'active') return ApiResponse::error('The webhook endpoint is inactive.', 409, null, 'WEBHOOK_ENDPOINT_INACTIVE');
        $delivery->forceFill(['status' => 'retry_queued', 'retry_count' => ((int) $delivery->retry_count) + 1, 'queued_at' => now()])->save();
        ActivityLogger::record($request, 'webhook_delivery.retry_queued', $delivery, 'Webhook delivery retry requested.', ['retry_count' => $delivery->retry_count]);
        return ApiResponse::success(['delivery' => $this->presentDelivery($delivery->fresh()->load('endpoint'))], 'Webhook delivery retry queued.');
    }

    private function load(PlatformWebhookEndpoint $endpoint): PlatformWebhookEndpoint { return $endpoint->load('tenant'); }
    private function presentEndpoint(PlatformWebhookEndpoint $endpoint): array { $data = $endpoint->toArray(); unset($data['secret_hash']); return $data; }
    private function presentDelivery(PlatformWebhookDelivery $delivery): array { $data = $delivery->toArray(); $data['payload'] = $this->redact($delivery->payload); return $data; }
    private function redact(mixed $value): mixed { if (! is_array($value)) return $value; $out = []; foreach ($value as $key => $child) $out[$key] = preg_match('/(password|secret|token|authorization|credential|private[_-]?key|api[_-]?key)/i', (string) $key) ? '[REDACTED]' : $this->redact($child); return $out; }
    private function auditData(PlatformWebhookEndpoint $endpoint): array { return $endpoint->only(['uuid', 'tenant_id', 'name', 'url', 'events', 'status']); }
    private function perPage(Request $request): int { return min(max($request->integer('per_page', 25), 1), 100); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]; }
}
