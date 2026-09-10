<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlanFeature extends Model
{
    protected $table = 'plan_features';

    protected $fillable = [];

    protected $casts = ['plan_id' => 'integer', 'feature_id' => 'integer', 'metadata' => 'array'];
    
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function feature(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
