<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformInvoiceItem extends Model
{
    protected $table = 'platform_invoice_items';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformInvoice::class, 'platform_invoice_id');
    }
}
