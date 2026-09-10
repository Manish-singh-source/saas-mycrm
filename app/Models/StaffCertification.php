<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffCertification extends Model
{
    protected $table = 'staff_certifications';

    public $timestamps = false;

    protected $guarded = ['id'];
}
