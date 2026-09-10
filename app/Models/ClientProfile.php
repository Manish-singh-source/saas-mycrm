<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ClientProfile extends Model
{
    protected $table = 'client_profiles';

    protected $guarded = ['id'];
}
