<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskWatcher extends Model
{
    protected $table = 'task_watchers';

    public $timestamps = false;

    protected $guarded = ['id'];
}
