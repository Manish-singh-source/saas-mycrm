<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskAssignment extends Model
{
    protected $table = 'task_assignments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
