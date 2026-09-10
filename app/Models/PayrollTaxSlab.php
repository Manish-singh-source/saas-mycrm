<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollTaxSlab extends Model
{
    protected $table = 'payroll_tax_slabs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
