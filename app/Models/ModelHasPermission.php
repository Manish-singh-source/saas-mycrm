<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ModelHasPermission extends Model
{
    protected $table = 'model_has_permissions';

    public $timestamps = false;

    protected $guarded = ['id'];
}
