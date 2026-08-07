<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait HasSharedTenantVisibility
{
    protected static function bootHasSharedTenantVisibility(): void
    {
        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if ($context->hasTenant() && $model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', $context->id());
            }
        });
    }

    public function scopeVisibleToCurrentTenant(Builder $query): Builder
    {
        $context = app(TenantContext::class);
        $context->assertResolved();

        return $query->where(function (Builder $nested) use ($context): void {
            $nested->whereNull($this->getTable().'.tenant_id')
                ->orWhere($this->getTable().'.tenant_id', $context->id());
        });
    }

    public function scopePlatformVisible(Builder $query): Builder
    {
        return $query->whereNull($this->getTable().'.tenant_id');
    }

    public static function createForCurrentTenant(array $attributes): static
    {
        $context = app(TenantContext::class);
        $context->assertResolved();
        $attributes['tenant_id'] = $context->id();

        return static::query()->create($attributes);
    }

    public static function createForPlatform(array $attributes): static
    {
        if (array_key_exists('tenant_id', $attributes) && $attributes['tenant_id'] !== null) {
            throw new LogicException('Platform-visible shared records must use a null tenant_id.');
        }

        $attributes['tenant_id'] = null;

        return static::query()->create($attributes);
    }
}