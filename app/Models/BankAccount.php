<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $guarded = ['id'];
}
