<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffAppraisal extends Model
{
    protected $table = 'staff_appraisals';

    public $timestamps = false;

    protected $guarded = ['id'];
}
