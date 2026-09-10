<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Industry extends Model
{
    use HasFactory;

    protected $table = 'industries';

    protected $fillable = ['name', 'code', 'description', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
    
    public function tenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class);
    }

}
