<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QueueJobLog extends Model
{
    protected $table = 'queue_job_logs';

    public $timestamps = false;

    protected $fillable = ['queue', 'job_name', 'status', 'attempts', 'exception', 'started_at', 'finished_at'];

    protected $casts = ['attempts' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
}
