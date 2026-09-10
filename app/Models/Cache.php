<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Cache extends Model
{
    protected $table = 'cache';

    public $timestamps = false;

    protected $guarded = ['id'];
}
