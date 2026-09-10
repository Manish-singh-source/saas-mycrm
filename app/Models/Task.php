<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Task extends Model
{
    use SoftDeletes;

    protected $table = 'tasks';

    protected $guarded = ['id'];
}
