<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AddonPlan extends Model
{
    protected $table = 'addon_plans';

    protected $fillable = ['uuid', 'name', 'code', 'pricing_type', 'price', 'currency', 'is_public', 'status'];

    protected $casts = ['price' => 'decimal:2', 'is_public' => 'boolean'];

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_addons', 'addon_plan_id', 'plan_id');
    }

    public function subscriptionAddons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionAddon::class, 'addon_plan_id');
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Subscription::class, 'subscription_addons', 'addon_plan_id', 'subscription_id')->withPivot(['quantity', 'unit_price', 'starts_at', 'ends_at', 'status']);
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
