<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MeetingRoom extends Model
{
    protected $table = 'meeting_rooms';

    public $timestamps = false;

    protected $guarded = ['id'];
}
