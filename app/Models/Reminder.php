<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Reminder extends Model
{
    protected $table = 'reminders';

    protected $guarded = ['id'];
}
