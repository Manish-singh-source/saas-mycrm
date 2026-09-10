<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffEmploymentHistory extends Model
{
    protected $table = 'staff_employment_history';

    public $timestamps = false;

    protected $guarded = ['id'];
}
