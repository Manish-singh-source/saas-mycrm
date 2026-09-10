<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SecurityEvent extends Model
{
    protected $table = 'security_events';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['tenant_id' => 'integer', 'user_id' => 'integer', 'metadata' => 'array', 'created_at' => 'datetime'];
    protected $appends = ['tenant_name', 'user_name', 'review_status'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function getTenantNameAttribute(): ?string { return $this->tenant?->organization_name ?? $this->tenant?->name; }
    public function getUserNameAttribute(): ?string { return $this->user?->name ?? $this->user?->email; }
    public function getReviewStatusAttribute(): ?string { return is_array($this->metadata) ? ($this->metadata['review']['status'] ?? null) : null; }
}