<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Subscription extends Model
{
    use SoftDeletes;

    protected $table = 'subscriptions';

    protected $guarded = ['id'];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Plan::class, 'plan_id'); }
    public function platformInvoices(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformInvoice::class, 'subscription_id'); }
    public function platformPayments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PlatformPayment::class, 'subscription_id'); }
    public function addonAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubscriptionAddon::class, 'subscription_id'); }

    public function redemptions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(CouponRedemption::class, 'subscription_id'); }
    public function usage(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubscriptionUsage::class, 'subscription_id'); }
    public function versions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubscriptionVersion::class, 'subscription_id'); }
    public function renewals(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubscriptionRenewal::class, 'subscription_id'); }
}
