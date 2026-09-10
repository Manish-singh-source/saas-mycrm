<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffShiftAssignment extends Model
{
    protected $table = 'staff_shift_assignments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
