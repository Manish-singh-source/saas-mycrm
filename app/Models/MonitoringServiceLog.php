<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MonitoringServiceLog extends Model
{
    protected $table = 'monitoring_service_logs';

    public $timestamps = false;

    protected $fillable = ['service_id', 'status', 'response_time_ms', 'message', 'checked_at'];

    protected $casts = ['service_id' => 'integer', 'response_time_ms' => 'integer', 'checked_at' => 'datetime'];

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(MonitoringService::class, 'service_id'); 
    }

}
