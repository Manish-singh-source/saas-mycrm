<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffAsset extends Model
{
    protected $table = 'staff_assets';

    public $timestamps = false;

    protected $guarded = ['id'];
}
