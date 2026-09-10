<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantBackupRun extends Model
{
    protected $table = 'tenant_backup_runs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
