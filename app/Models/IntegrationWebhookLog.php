<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IntegrationWebhookLog extends Model
{
    protected $table = 'integration_webhook_logs';

    public $timestamps = false;

    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
    public function webhook(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(IntegrationWebhook::class, 'webhook_id'); }
}
