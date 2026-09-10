<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Job extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
