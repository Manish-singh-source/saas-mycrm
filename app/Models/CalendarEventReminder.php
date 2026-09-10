<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CalendarEventReminder extends Model
{
    protected $table = 'calendar_event_reminders';

    public $timestamps = false;

    protected $guarded = ['id'];
}
