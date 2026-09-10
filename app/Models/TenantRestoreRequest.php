<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantRestoreRequest extends Model
{
    protected $table = 'tenant_restore_requests';

    public $timestamps = false;

    protected $guarded = ['id'];
}
