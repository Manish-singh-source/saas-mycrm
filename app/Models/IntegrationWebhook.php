<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IntegrationWebhook extends Model
{
    protected $table = 'integration_webhooks';

    public $timestamps = false;

    protected $guarded = ['id'];
    public function integration(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(TenantIntegration::class, 'tenant_integration_id'); }
    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(IntegrationWebhookLog::class, 'webhook_id'); }
}
