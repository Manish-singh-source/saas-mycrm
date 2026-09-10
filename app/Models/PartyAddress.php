<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PartyAddress extends Model
{
    protected $table = 'party_addresses';

    protected $guarded = ['id'];
}
