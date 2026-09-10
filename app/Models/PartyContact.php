<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PartyContact extends Model
{
    use SoftDeletes;

    protected $table = 'party_contacts';

    protected $guarded = ['id'];
}
