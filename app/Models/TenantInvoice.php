<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenantInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_invoices';

    protected $guarded = ['id'];
}
