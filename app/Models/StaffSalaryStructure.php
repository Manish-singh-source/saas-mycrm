<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffSalaryStructure extends Model
{
    protected $table = 'staff_salary_structures';

    public $timestamps = false;

    protected $guarded = ['id'];
}
