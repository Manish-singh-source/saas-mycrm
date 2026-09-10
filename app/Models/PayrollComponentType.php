<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollComponentType extends Model
{
    protected $table = 'payroll_component_types';

    public $timestamps = false;

    protected $guarded = ['id'];
}
