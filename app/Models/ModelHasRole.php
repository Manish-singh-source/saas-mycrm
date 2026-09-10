<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';

    public $timestamps = false;

    protected $guarded = ['id'];
}
