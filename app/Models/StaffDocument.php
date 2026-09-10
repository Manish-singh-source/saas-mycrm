<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffDocument extends Model
{
    protected $table = 'staff_documents';

    public $timestamps = false;

    protected $guarded = ['id'];
}
