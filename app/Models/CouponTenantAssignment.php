<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CouponTenantAssignment extends Model
{
    protected $table = 'coupon_tenant_assignments';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function coupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Coupon::class, 'coupon_id'); }
    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Tenant::class, 'tenant_id'); }
}
