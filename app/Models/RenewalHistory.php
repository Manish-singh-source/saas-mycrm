<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RenewalHistory extends Model
{
    protected $table = 'renewal_history';

    public $timestamps = false;

    protected $guarded = ['id'];
}
