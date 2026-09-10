<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffTraining extends Model
{
    protected $table = 'staff_training';

    public $timestamps = false;

    protected $guarded = ['id'];
}
