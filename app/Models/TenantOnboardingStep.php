<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantOnboardingStep extends Model
{
    protected $table = 'tenant_onboarding_steps';

    protected $guarded = ['id'];
    protected $casts = ['metadata' => 'array', 'tenant_id' => 'integer', 'updated_by' => 'integer'];
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'updated_by'); }
}
