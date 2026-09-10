<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TenantExpense extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_expenses';

    protected $guarded = ['id'];
}
