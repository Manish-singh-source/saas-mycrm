<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantSetting extends Model
{
    protected $table = 'tenant_settings';

    protected $guarded = ['id'];
}
