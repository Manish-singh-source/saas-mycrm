<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';

    protected $guarded = ['id'];
}
