<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class VideoMeeting extends Model
{
    protected $table = 'video_meetings';

    public $timestamps = false;

    protected $guarded = ['id'];
}
