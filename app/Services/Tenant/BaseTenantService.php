<?php

namespace App\Services\Tenant;

use App\Services\Shared\BaseService;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

abstract class BaseTenantService extends BaseService
{
    public function __construct(protected readonly TenantContext $tenantContext) {}

    protected function tenantId(): int
    {
        $this->tenantContext->assertResolved();

        return (int) $this->tenantContext->id();
    }

    protected function assertTenantIsResolved(mixed $tenant = null): void
    {
        $this->tenantContext->assertResolved();

        if ($tenant !== null) {
            $tenantId = is_object($tenant) ? ($tenant->id ?? null) : $tenant;
            $this->tenantContext->assertTenantId($tenantId);
        }
    }

    protected function assertOwns(object|array|null $record): void
    {
        abort_if(! $this->tenantContext->owns($record), 403, 'Record does not belong to the current tenant.');
    }

    protected function withTenantId(array $attributes): array
    {
        $tenantId = $this->tenantId();

        if (isset($attributes['tenant_id']) && (int) $attributes['tenant_id'] !== $tenantId) {
            abort(403, 'Cannot create records for another tenant.');
        }

        $attributes['tenant_id'] = $tenantId;

        return $attributes;
    }

    protected function createTenantRecord(string $modelClass, array $attributes): Model
    {
        /** @var class-string<Model> $modelClass */
        return $modelClass::query()->create($this->withTenantId($attributes));
    }
}