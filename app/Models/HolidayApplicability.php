<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HolidayApplicability extends Model
{
    protected $table = 'holiday_applicabilities';

    public $timestamps = false;

    protected $guarded = ['id'];
}
