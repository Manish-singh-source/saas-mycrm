<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BusinessType extends Model
{
    use HasFactory;

    protected $table = 'business_types';

    protected $fillable = ['name', 'code', 'description', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
    
    public function tenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class);
    }

}
