<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionAddon extends Model
{
    protected $table = 'subscription_addons';

    protected $guarded = ['id'];

    protected $casts = ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Subscription::class, 'subscription_id'); }
    public function addonPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(AddonPlan::class, 'addon_plan_id'); }
}
