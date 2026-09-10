<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Permission extends Model {
    protected $table='permissions'; protected $guarded=['id']; protected $casts=['is_system'=>'boolean'];
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class,'role_has_permissions','permission_id','role_id'); }
}

