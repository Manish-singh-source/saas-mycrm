<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantInvoiceItem extends Model
{
    protected $table = 'tenant_invoice_items';

    public $timestamps = false;

    protected $guarded = ['id'];
}
