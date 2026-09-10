<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CalendarEventAttendee extends Model
{
    protected $table = 'calendar_event_attendees';

    public $timestamps = false;

    protected $guarded = ['id'];
}
