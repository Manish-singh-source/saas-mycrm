<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskTimeLog extends Model
{
    protected $table = 'task_time_logs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
