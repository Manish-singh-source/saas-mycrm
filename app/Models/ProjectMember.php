<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProjectMember extends Model
{
    protected $table = 'project_members';

    public $timestamps = false;

    protected $guarded = ['id'];
}
