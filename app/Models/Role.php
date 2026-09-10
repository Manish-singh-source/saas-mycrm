<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Role extends Model {
    protected $table='roles'; protected $guarded=['id']; protected $casts=['is_system'=>'boolean'];
    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class,'role_has_permissions','role_id','permission_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class,'model_has_roles','role_id','model_id')->wherePivot('tenant_id',$this->tenant_id)->wherePivot('model_type',User::class); }
}

