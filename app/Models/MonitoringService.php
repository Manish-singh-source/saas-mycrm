<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MonitoringService extends Model
{
    protected $table = 'monitoring_services';

    protected $fillable = ['name', 'code', 'service_type', 'status', 'check_interval_seconds'];

    protected $casts = ['check_interval_seconds' => 'integer'];

    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany { 
        return $this->hasMany(MonitoringServiceLog::class, 'service_id'); 
    }

}
