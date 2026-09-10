<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RoleHasPermission extends Model
{
    protected $table = 'role_has_permissions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
