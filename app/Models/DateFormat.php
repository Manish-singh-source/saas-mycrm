<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DateFormat extends Model
{
    use HasFactory;

    protected $table = 'date_formats';

    protected $fillable = ['name', 'code', 'format', 'example', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
