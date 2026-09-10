<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LeadConversionHistory extends Model
{
    protected $table = 'lead_conversion_history';

    public $timestamps = false;

    protected $guarded = ['id'];
}
