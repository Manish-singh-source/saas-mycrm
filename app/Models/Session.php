<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Session extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
