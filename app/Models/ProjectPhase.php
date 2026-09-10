<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjectPhase extends Model
{
    protected $table = 'project_phases';

    public $timestamps = false;

    protected $guarded = ['id'];
}
