<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollPfSetting extends Model
{
    protected $table = 'payroll_pf_settings';

    public $timestamps = false;

    protected $guarded = ['id'];
}
