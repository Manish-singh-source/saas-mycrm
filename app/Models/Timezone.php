<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Timezone extends Model
{
    use HasFactory;

    protected $table = 'timezones';

    protected $fillable = ['name', 'identifier', 'utc_offset', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
