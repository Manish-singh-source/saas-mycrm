<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IntegrationRateLimit extends Model
{
    protected $table = 'integration_rate_limits';

    public $timestamps = false;

    protected $guarded = ['id'];
    public function integration(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(TenantIntegration::class, 'tenant_integration_id'); }
}
