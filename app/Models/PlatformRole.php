<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformRole extends Model
{
    protected $table = 'platform_roles';

    protected $fillable = ['name', 'display_name', 'guard_name', 'description', 'is_system', 'status'];

    protected $casts = [
        'is_system' => 'boolean',
    ];
    
    public function permissions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PlatformPermission::class, 'platform_role_has_permissions', 'role_id', 'permission_id');
    }

    public function platformUsers(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(PlatformUser::class, 'model', 'platform_model_has_roles', 'role_id', 'model_id');
    }

}
