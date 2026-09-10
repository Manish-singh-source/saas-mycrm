<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffEmergencyContact extends Model
{
    protected $table = 'staff_emergency_contacts';

    public $timestamps = false;

    protected $guarded = ['id'];
}
