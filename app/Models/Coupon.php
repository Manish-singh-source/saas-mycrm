<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Coupon extends Model
{
    use SoftDeletes;

    protected $table = 'coupons';

    protected $fillable = ['uuid', 'code', 'name', 'discount_type', 'discount_value', 'starts_at', 'expires_at', 'max_redemptions', 'status'];

    protected $casts = ['discount_value' => 'decimal:2', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'max_redemptions' => 'integer'];

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'coupon_plan_assignments', 'coupon_id', 'plan_id');
    }

    public function tenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'coupon_tenant_assignments', 'coupon_id', 'tenant_id');
    }

    public function redemptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'coupon_id');
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
