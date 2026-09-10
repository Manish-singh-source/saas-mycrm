<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantUsageSnapshot extends Model
{
    protected $table = 'tenant_usage_snapshots';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $appends = ['tenant_name'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'users_count' => 'integer', 'storage_bytes' => 'integer', 'api_requests' => 'integer', 'projects_count' => 'integer', 'invoices_count' => 'integer'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }

    public function getTenantNameAttribute(): ?string
    {
        return $this->tenant?->organization_name ?? $this->tenant?->display_name ?? $this->tenant?->name;
    }
}

