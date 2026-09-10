<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CacheLock extends Model
{
    protected $table = 'cache_locks';

    public $timestamps = false;

    protected $guarded = ['id'];
}
