<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RenewalReminder extends Model
{
    protected $table = 'renewal_reminders';

    public $timestamps = false;

    protected $guarded = ['id'];
}
