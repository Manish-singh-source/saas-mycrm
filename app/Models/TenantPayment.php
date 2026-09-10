<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantPayment extends Model
{
    protected $table = 'tenant_payments';

    protected $guarded = ['id'];
}
