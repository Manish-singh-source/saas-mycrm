<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HolidayCalendar extends Model
{
    protected $table = 'holiday_calendars';

    protected $guarded = ['id'];
}
