<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformRoleHasPermission extends Model
{
    protected $table = 'platform_role_has_permissions';

    public $timestamps = false;

    protected $fillable = [];

    protected $casts = [
        'role_id' => 'integer', 'permission_id' => 'integer',
    ];

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformRole::class, 'role_id'); 
    }

    public function permission(): \Illuminate\Database\Eloquent\Relations\BelongsTo { 
        return $this->belongsTo(PlatformPermission::class, 'permission_id'); 
    }

}
