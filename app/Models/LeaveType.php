<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeaveType extends Model
{
    protected $table='leave_types';
    public $timestamps=false;
    protected $guarded=['id'];
    protected $casts=['paid'=>'boolean','carry_forward'=>'boolean'];
    public function requests(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LeaveRequest::class,'leave_type_id'); }
    public function balances(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(LeaveBalance::class,'leave_type_id'); }
}