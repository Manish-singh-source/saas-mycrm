<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PlatformWebhookEndpoint extends Model
{
    use SoftDeletes;

    protected $table = 'platform_webhook_endpoints';

    protected $guarded = ['id'];

    protected $casts = [
        'events' => 'array',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function deliveries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformWebhookDelivery::class, 'platform_webhook_endpoint_id');
    }
}
