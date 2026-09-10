<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CouponPlanAssignment extends Model
{
    protected $table = 'coupon_plan_assignments';

    public $timestamps = false;

    protected $fillable = [];

    protected $casts = ['coupon_id' => 'integer', 'plan_id' => 'integer'];

    public function coupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(Coupon::class, 'coupon_id'); 
    }
    
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(Plan::class, 'plan_id'); 
    }

}
