<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = ['name', 'code', 'symbol', 'decimal_places', 'status'];

    protected $casts = [
        'decimal_places' => 'integer',
        'sort_order' => 'integer',
    ];
}
