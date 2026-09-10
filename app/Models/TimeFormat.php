<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TimeFormat extends Model
{
    use HasFactory;

    protected $table = 'time_formats';

    protected $fillable = ['name', 'code', 'format', 'example', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
