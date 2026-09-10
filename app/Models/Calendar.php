<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Calendar extends Model
{
    protected $table = 'calendars';

    protected $guarded = ['id'];
}
