<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class State extends Model
{
    use HasFactory;

    protected $table = 'states';

    protected $fillable = [
        'country_id',
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'country_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    
    public function cities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(City::class);
    }

}
