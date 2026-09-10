<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollReimbursement extends Model
{
    protected $table = 'payroll_reimbursements';

    public $timestamps = false;

    protected $guarded = ['id'];
}
