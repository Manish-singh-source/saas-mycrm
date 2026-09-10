<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SystemIncident extends Model
{
    protected $table = 'system_incidents';

    protected $fillable = ['title', 'severity', 'status', 'started_at', 'resolved_at', 'summary', 'resolution_notes', 'resolved_by'];

    protected $casts = ['started_at' => 'datetime', 'resolved_at' => 'datetime', 'resolved_by' => 'integer'];

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(ActivityLog::class, 'subject'); }

    public function resolvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'resolved_by'); }
}
