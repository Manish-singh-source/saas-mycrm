<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserPreference extends Model
{
    protected $table = 'user_preferences';

    protected $guarded = ['id'];
}
