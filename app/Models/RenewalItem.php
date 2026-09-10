<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RenewalItem extends Model
{
    protected $table = 'renewal_items';

    public $timestamps = false;

    protected $guarded = ['id'];
}
