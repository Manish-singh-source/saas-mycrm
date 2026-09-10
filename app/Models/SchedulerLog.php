<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SchedulerLog extends Model
{
    protected $table = 'scheduler_logs';

    public $timestamps = false;

    protected $fillable = ['command', 'status', 'output', 'started_at', 'finished_at'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
