<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TeamAssignment extends Model
{
    protected $table = 'team_assignments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
