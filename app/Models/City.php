<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'state_id',
        'name',
        'status',
    ];

    protected $casts = [
        'state_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function state(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
