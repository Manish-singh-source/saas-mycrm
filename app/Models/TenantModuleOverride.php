<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantModuleOverride extends Model
{
    protected $table = 'tenant_module_overrides';

    protected $guarded = ['id'];

    public function module(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Module::class, 'module_code', 'code'); }
    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'updated_by'); }

    protected $casts = ['enabled' => 'boolean', 'limits' => 'array', 'metadata' => 'array'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
