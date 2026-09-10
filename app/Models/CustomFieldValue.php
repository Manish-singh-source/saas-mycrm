<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CustomFieldValue extends Model
{
    protected $table = 'custom_field_values';

    protected $guarded = ['id'];
}
