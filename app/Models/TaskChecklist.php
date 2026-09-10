<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskChecklist extends Model
{
    protected $table = 'task_checklists';

    public $timestamps = false;

    protected $guarded = ['id'];
}
