<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformWebhookDelivery extends Model
{
    protected $table = 'platform_webhook_deliveries';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'queued_at' => 'datetime',
    ];

    public function endpoint(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformWebhookEndpoint::class, 'platform_webhook_endpoint_id');
    }
}
