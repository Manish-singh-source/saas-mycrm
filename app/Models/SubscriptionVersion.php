<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionVersion extends Model
{
    protected $table = 'subscription_versions';
    protected $guarded = ['id'];
    protected $casts = ['version' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'pricing_snapshot' => 'array', 'feature_snapshot' => 'array'];

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Plan::class, 'plan_id'); }
    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Subscription::class, 'subscription_id'); }
}
