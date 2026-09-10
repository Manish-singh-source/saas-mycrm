<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MonitoringAlert extends Model
{
    protected $table = 'monitoring_alerts';

    public $timestamps = false;

    protected $fillable = ['alertable_type', 'alertable_id', 'severity', 'message', 'status', 'triggered_at', 'resolved_at', 'resolution_notes', 'resolved_by'];

    protected $casts = ['alertable_id' => 'integer', 'triggered_at' => 'datetime', 'resolved_at' => 'datetime', 'resolved_by' => 'integer'];

    public function alertable(): \Illuminate\Database\Eloquent\Relations\MorphTo { return $this->morphTo(); }

    public function resolvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(PlatformUser::class, 'resolved_by'); }
}
