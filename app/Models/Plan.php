<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Plan extends Model
{
    use SoftDeletes;

    protected $table = 'plans';

    protected $fillable = ['uuid', 'name', 'code', 'description', 'billing_cycle', 'base_price', 'currency', 'trial_days', 'is_custom', 'is_public', 'status'];

    protected $casts = ['base_price' => 'decimal:2', 'trial_days' => 'integer', 'is_custom' => 'boolean', 'is_public' => 'boolean'];

    public function features(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(Feature::class, 'plan_features', 'plan_id', 'feature_id')->withPivot(['value', 'metadata']); }
    public function planFeatures(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlanFeature::class, 'plan_id'); }
    public function addons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(AddonPlan::class, 'plan_addons', 'plan_id', 'addon_plan_id'); }
    public function planAddons(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlanAddon::class, 'plan_id'); }
    public function coupons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(Coupon::class, 'coupon_plan_assignments', 'plan_id', 'coupon_id'); }
    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Subscription::class, 'plan_id'); }
    public function subscriptionVersions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubscriptionVersion::class, 'plan_id'); }
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(ActivityLog::class, 'subject'); }
}
