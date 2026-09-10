<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IntegrationProvider extends Model
{
    protected $table = 'integration_providers';

    protected $fillable = ['name', 'code', 'category', 'auth_type', 'status', 'metadata'];

    protected $casts = ['metadata' => 'array'];

    public function tenantIntegrations(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(TenantIntegration::class, 'provider_id'); }
}
