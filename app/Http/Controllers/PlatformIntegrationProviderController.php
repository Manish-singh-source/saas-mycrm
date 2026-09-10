<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIntegrationProviderRequest;
use App\Http\Requests\UpdateIntegrationProviderRequest;
use App\Models\IntegrationProvider;
use App\Support\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformIntegrationProviderController extends Controller
{
    public function index(Request $request): mixed
    {
        $query = IntegrationProvider::query()->with(['tenantIntegrations.tenant'])->latest('created_at');
        foreach (['category', 'status', 'auth_type'] as $field) {
            if ($request->filled($field)) $query->where($field, (string) $request->string($field));
        }
        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        $page = $query->paginate(min(max($request->integer('per_page', 25), 1), 100))->withQueryString();
        return ApiResponse::success($page->items(), 'Integration providers fetched.', 200, [
            'current_page' => $page->currentPage(), 'per_page' => $page->perPage(),
            'total' => $page->total(), 'last_page' => $page->lastPage(),
        ]);
    }

    public function store(StoreIntegrationProviderRequest $request): mixed
    {
        $data = $request->validated();
        $data['code'] = $this->uniqueCode((string) $data['name']);
        $provider = IntegrationProvider::create($data + ['status' => $data['status'] ?? 'active']);
        ActivityLogger::record($request, 'integration_provider.created', $provider, 'Integration provider created.', $this->auditValues($provider));
        return ApiResponse::success(['provider' => $this->load($provider)], 'Integration provider created successfully.', 201);
    }

    public function update(UpdateIntegrationProviderRequest $request, string $providerCode): mixed
    {
        $provider = IntegrationProvider::where('code', $providerCode)->first();
        if (! $provider) return ApiResponse::error('Integration provider not found.', 404, null, 'INTEGRATION_PROVIDER_NOT_FOUND');
        $data = $request->validated();
        $oldValues = $this->auditValues($provider);
        $provider->update($data);
        ActivityLogger::record($request, 'integration_provider.updated', $provider, 'Integration provider updated.', ['old' => $oldValues, 'new' => $this->auditValues($provider, $data)]);
        return ApiResponse::success(['provider' => $this->load($provider->fresh())], 'Integration provider updated successfully.');
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        $code = $base ?: 'provider';
        $suffix = 2;
        while (IntegrationProvider::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }
        return $code;
    }

    private function load(IntegrationProvider $provider): IntegrationProvider
    {
        return $provider->load(['tenantIntegrations.tenant']);
    }

    private function auditValues(IntegrationProvider $provider, array $values = []): array
    {
        return collect($values ?: $provider->toArray())->except(['tenant_integrations'])->all();
    }
}
