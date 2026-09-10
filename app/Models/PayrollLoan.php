<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollLoan extends Model
{
    protected $table = 'payroll_loans';

    public $timestamps = false;

    protected $guarded = ['id'];
}
