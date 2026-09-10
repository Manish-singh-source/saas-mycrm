<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantExpenseItem extends Model
{
    protected $table = 'tenant_expense_items';

    public $timestamps = false;

    protected $guarded = ['id'];
}
