<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TeamMember extends Model
{
    protected $table = 'team_members';

    protected $guarded = ['id'];
}
