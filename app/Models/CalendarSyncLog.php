<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CalendarSyncLog extends Model
{
    protected $table = 'calendar_sync_logs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
