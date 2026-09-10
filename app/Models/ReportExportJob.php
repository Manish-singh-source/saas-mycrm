<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ReportExportJob extends Model
{
    protected $table = 'report_export_jobs';

    protected $guarded = ['id'];
    protected $casts = ['filters' => 'array'];
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'created_by'); }
    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(File::class, 'file_id'); }
}
