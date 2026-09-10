<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Taggable extends Model
{
    protected $table = 'taggables';

    public $timestamps = false;

    protected $guarded = ['id'];
}
