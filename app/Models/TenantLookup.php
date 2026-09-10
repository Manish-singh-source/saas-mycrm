<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantLookup extends Model
{
    protected $table = 'tenant_lookups';

    protected $guarded = ['id'];
}
