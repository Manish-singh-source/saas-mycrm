<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HolidayGroupMember extends Model
{
    protected $table = 'holiday_group_members';

    public $timestamps = false;

    protected $guarded = ['id'];
}
