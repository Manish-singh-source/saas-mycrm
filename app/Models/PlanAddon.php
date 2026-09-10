<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlanAddon extends Model
{
    protected $table = 'plan_addons';

    protected $fillable = [];

    protected $casts = ['plan_id' => 'integer', 'addon_plan_id' => 'integer'];

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
    
    public function addonPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AddonPlan::class, 'addon_plan_id');
    }
}
