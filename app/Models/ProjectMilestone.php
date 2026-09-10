<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjectMilestone extends Model
{
    protected $table = 'project_milestones';

    public $timestamps = false;

    protected $guarded = ['id'];
}
