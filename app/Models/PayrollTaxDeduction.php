<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollTaxDeduction extends Model
{
    protected $table = 'payroll_tax_deductions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
