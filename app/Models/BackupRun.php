<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BackupRun extends Model
{
    protected $table = 'backup_runs';

    public $timestamps = false;

    protected $fillable = ['uuid', 'backup_type', 'status', 'file_id', 'started_at', 'finished_at', 'error_message'];

    protected $casts = ['file_id' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(File::class, 'file_id'); }
}
