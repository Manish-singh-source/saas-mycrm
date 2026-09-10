<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    protected $appends = ['actor_name', 'tenant_name'];

    public function actorPlatformUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformUser::class, 'actor_platform_user_id');
    }

    public function actorUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function getActorNameAttribute(): ?string
    {
        return $this->actorPlatformUser?->name ?? $this->actorUser?->name ?? $this->actorUser?->email;
    }

    public function getTenantNameAttribute(): ?string
    {
        return $this->tenant?->organization_name ?? $this->tenant?->name;
    }
}