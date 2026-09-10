<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $appends = ['tenant_name', 'user_name'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }

    public function getTenantNameAttribute(): ?string
    {
        return $this->tenant?->organization_name ?? $this->tenant?->display_name ?? $this->tenant?->name;
    }

    public function getUserNameAttribute(): ?string
    {
        return $this->user?->display_name ?? $this->user?->name ?? $this->user?->email;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}

