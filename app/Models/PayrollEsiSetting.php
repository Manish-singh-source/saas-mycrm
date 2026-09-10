<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollEsiSetting extends Model
{
    protected $table = 'payroll_esi_settings';

    public $timestamps = false;

    protected $guarded = ['id'];
}
