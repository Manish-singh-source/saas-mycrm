<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollCycle extends Model
{
    protected $table = 'payroll_cycles';

    protected $guarded = ['id'];
}
