<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollComponent extends Model
{
    protected $table = 'payroll_components';

    public $timestamps = false;

    protected $guarded = ['id'];
}
