<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CouponRedemption extends Model
{
    protected $table = 'coupon_redemptions';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['discount_amount' => 'decimal:2', 'redeemed_at' => 'datetime'];

    public function coupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Coupon::class, 'coupon_id'); }
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Subscription::class, 'subscription_id'); }
    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id'); }
}
