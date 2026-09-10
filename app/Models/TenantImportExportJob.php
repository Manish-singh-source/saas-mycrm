<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantImportExportJob extends Model
{
    protected $table = 'tenant_import_export_jobs';

    protected $guarded = ['id'];
}
