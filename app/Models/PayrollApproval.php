<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollApproval extends Model
{
    protected $table = 'payroll_approvals';

    public $timestamps = false;

    protected $guarded = ['id'];
}
