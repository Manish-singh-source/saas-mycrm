<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformModelHasPermission extends Model
{
    protected $table = 'platform_model_has_permissions';

    public $timestamps = false;

    protected $fillable = [];

    protected $casts = [
        'permission_id' => 'integer', 'model_id' => 'integer',
    ];

    public function permission(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformPermission::class, 'permission_id'); 
    }

    public function model(): \Illuminate\Database\Eloquent\Relations\MorphTo { 
        return $this->morphTo(); 
    }

}
