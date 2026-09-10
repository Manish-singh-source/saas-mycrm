<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollBankTransfer extends Model
{
    protected $table = 'payroll_bank_transfers';

    public $timestamps = false;

    protected $guarded = ['id'];
}
