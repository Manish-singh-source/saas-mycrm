<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskComment extends Model
{
    protected $table = 'task_comments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
