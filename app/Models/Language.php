<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Language extends Model
{
    use HasFactory;

    protected $table = 'languages';

    protected $fillable = ['name', 'code', 'iso3', 'native_name', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
