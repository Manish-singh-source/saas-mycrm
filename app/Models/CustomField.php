<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomField extends Model
{
    protected $table = 'custom_fields';

    protected $guarded = ['id'];
}
