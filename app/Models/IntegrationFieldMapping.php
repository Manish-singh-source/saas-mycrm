<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IntegrationFieldMapping extends Model
{
    protected $table = 'integration_field_mappings';

    public $timestamps = false;

    protected $guarded = ['id'];
    protected $casts = ['transform_rule' => 'array'];
    public function integration(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(TenantIntegration::class, 'tenant_integration_id'); }
}
