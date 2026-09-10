<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TeamPermission extends Model
{
    protected $table = 'team_permissions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
