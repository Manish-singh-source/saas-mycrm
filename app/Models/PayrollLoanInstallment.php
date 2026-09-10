<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollLoanInstallment extends Model
{
    protected $table = 'payroll_loan_installments';

    public $timestamps = false;

    protected $guarded = ['id'];
}
