<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TaskChecklistItem extends Model
{
    protected $table = 'task_checklist_items';

    public $timestamps = false;

    protected $guarded = ['id'];
}
