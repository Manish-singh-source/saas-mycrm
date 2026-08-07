<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if ($context->hasTenant()) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $context->id());
            }
        });

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if (! $context->hasTenant()) {
                return;
            }

            $tenantId = $model->getAttribute('tenant_id');

            if ($tenantId === null) {
                $model->setAttribute('tenant_id', $context->id());
                return;
            }

            if ((int) $tenantId !== (int) $context->id()) {
                throw new LogicException('Cannot create a tenant-scoped model for another tenant.');
            }
        });
    }

    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where($this->getTable().'.tenant_id', (int) $tenantId);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $context = app(TenantContext::class);
        $context->assertResolved();

        return $query->withoutGlobalScope('tenant')->where($this->getTable().'.tenant_id', $context->id());
    }

    public function belongsToCurrentTenant(): bool
    {
        return app(TenantContext::class)->owns($this);
    }

    public static function createForCurrentTenant(array $attributes): static
    {
        $context = app(TenantContext::class);
        $context->assertResolved();

        $attributes['tenant_id'] = $context->id();

        return static::query()->create($attributes);
    }
}