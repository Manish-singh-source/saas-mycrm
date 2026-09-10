<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Payroll extends Model
{
    protected $table = 'payrolls';

    protected $guarded = ['id'];
}
