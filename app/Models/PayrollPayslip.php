<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollPayslip extends Model
{
    protected $table = 'payroll_payslips';

    public $timestamps = false;

    protected $guarded = ['id'];
}
