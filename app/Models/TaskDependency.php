<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskDependency extends Model
{
    protected $table = 'task_dependencies';

    public $timestamps = false;

    protected $guarded = ['id'];
}
