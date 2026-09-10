<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollOvertime extends Model
{
    protected $table = 'payroll_overtime';

    public $timestamps = false;

    protected $guarded = ['id'];
}
