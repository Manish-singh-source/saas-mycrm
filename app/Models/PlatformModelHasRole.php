<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformModelHasRole extends Model
{
    protected $table = 'platform_model_has_roles';

    public $timestamps = false;

    protected $fillable = [];

    protected $casts = [
        'role_id' => 'integer', 'model_id' => 'integer',
    ];

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformRole::class, 'role_id'); 
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo { 
        return $this->morphTo(); 
    }

}
