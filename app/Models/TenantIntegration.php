<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantIntegration extends Model
{
    protected $table = 'tenant_integrations';

    protected $guarded = ['id'];

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(IntegrationProvider::class, 'provider_id'); }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function credentials(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationCredential::class, 'tenant_integration_id'); }
    public function mappings(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationFieldMapping::class, 'tenant_integration_id'); }
    public function webhooks(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationWebhook::class, 'tenant_integration_id'); }
    public function syncJobs(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationSyncJob::class, 'tenant_integration_id'); }
    public function rateLimits(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationRateLimit::class, 'tenant_integration_id'); }
}
