<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjectTimeLog extends Model
{
    protected $table = 'project_time_logs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
