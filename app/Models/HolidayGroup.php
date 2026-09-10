<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HolidayGroup extends Model
{
    protected $table = 'holiday_groups';

    protected $guarded = ['id'];
}
