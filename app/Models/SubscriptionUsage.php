<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionUsage extends Model
{
    protected $table = 'subscription_usage';

    public $timestamps = false;


    public function feature(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Feature::class, 'feature_id'); }
}
