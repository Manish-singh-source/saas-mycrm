<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MeetingRoomBooking extends Model
{
    protected $table = 'meeting_room_bookings';

    public $timestamps = false;

    protected $guarded = ['id'];
}
