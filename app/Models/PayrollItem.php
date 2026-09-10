<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollItem extends Model
{
    protected $table = 'payroll_items';

    public $timestamps = false;

    protected $guarded = ['id'];
}
