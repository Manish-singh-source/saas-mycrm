<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformRefund extends Model
{
    protected $table = 'platform_refunds';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformPayment::class, 'platform_payment_id');
    }
}
