<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PlatformPermission extends Model
{
    protected $table = 'platform_permissions';

    protected $fillable = ['module', 'name', 'display_name', 'description', 'is_system', 'status'];

    protected $casts = [
        'is_system' => 'boolean',
    ];
    
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PlatformRole::class, 'platform_role_has_permissions', 'permission_id', 'role_id');
    }

    public function roleAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformRoleHasPermission::class, 'permission_id');
    }

    public function modelAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformModelHasPermission::class, 'permission_id');
    }

}
