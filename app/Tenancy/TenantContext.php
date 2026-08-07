<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantContext
{
    private ?Tenant $tenant = null;
    private array $settings = [];
    private array $enabledModules = [];
    private ?array $subscription = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->settings = [];
        $this->enabledModules = [];
        $this->subscription = null;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->settings = [];
        $this->enabledModules = [];
        $this->subscription = null;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function uuid(): ?string
    {
        return $this->tenant?->uuid;
    }

    public function slug(): ?string
    {
        return $this->tenant?->slug;
    }

    public function settings(): array
    {
        if (! $this->hasTenant()) {
            return [];
        }

        if ($this->settings === []) {
            $this->settings = DB::table('tenant_settings')
                ->where('tenant_id', $this->id())
                ->get(['group', 'key', 'value', 'value_type'])
                ->mapWithKeys(function (object $setting): array {
                    $value = $setting->value;

                    if ($setting->value_type === 'json' && is_string($value)) {
                        $decoded = json_decode($value, true);
                        $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
                    }

                    return [$setting->group.'.'.$setting->key => $value];
                })
                ->all();
        }

        return $this->settings;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings()[$key] ?? $default;
    }

    public function enabledModules(): array
    {
        if (! $this->hasTenant()) {
            return [];
        }

        if ($this->enabledModules === []) {
            $this->enabledModules = collect($this->settings())
                ->filter(fn (mixed $value, string $key): bool => str_starts_with($key, 'modules.') && str_ends_with($key, '.enabled') && (bool) $value)
                ->keys()
                ->map(fn (string $key): string => explode('.', $key)[1] ?? $key)
                ->values()
                ->all();
        }

        return $this->enabledModules;
    }

    public function subscription(): ?array
    {
        if (! $this->hasTenant()) {
            return null;
        }

        if ($this->subscription === null) {
            $subscription = DB::table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.tenant_id', $this->id())
                ->whereNull('subscriptions.deleted_at')
                ->orderByDesc('subscriptions.id')
                ->first([
                    'subscriptions.uuid',
                    'subscriptions.status',
                    'subscriptions.billing_cycle',
                    'subscriptions.starts_at',
                    'subscriptions.expires_at',
                    'subscriptions.trial_ends_at',
                    'plans.uuid as plan_uuid',
                    'plans.name as plan_name',
                    'plans.code as plan_code',
                ]);

            $this->subscription = $subscription ? (array) $subscription : [];
        }

        return $this->subscription === [] ? null : $this->subscription;
    }

    public function assertResolved(): void
    {
        abort_if(! $this->hasTenant(), 400, 'Tenant context is required.');
    }

    public function assertTenantId(int|string|null $tenantId): void
    {
        $this->assertResolved();
        abort_if((int) $tenantId !== (int) $this->id(), 403, 'Tenant context mismatch.');
    }

    public function owns(object|array|null $record): bool
    {
        if (! $this->hasTenant() || $record === null) {
            return false;
        }

        $tenantId = match (true) {
            $record instanceof Model => $record->getAttribute('tenant_id'),
            is_array($record) => $record['tenant_id'] ?? null,
            default => $record->tenant_id ?? null,
        };

        return (int) $tenantId === (int) $this->id();
    }

    public function canSeeShared(?int $tenantId): bool
    {
        return $tenantId === null || ($this->hasTenant() && (int) $tenantId === (int) $this->id());
    }

    public function snapshot(): array
    {
        return [
            'id' => $this->id(),
            'uuid' => $this->uuid(),
            'slug' => $this->slug(),
            'settings' => $this->settings(),
            'enabled_modules' => $this->enabledModules(),
            'subscription' => $this->subscription(),
        ];
    }
}