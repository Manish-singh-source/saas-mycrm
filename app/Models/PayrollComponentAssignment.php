<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollComponentAssignment extends Model
{
    protected $table = 'payroll_component_assignments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
