<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommunicationLog extends Model
{
    protected $table = 'communication_logs';

    public $timestamps = false;

    protected $guarded = ['id'];
}
