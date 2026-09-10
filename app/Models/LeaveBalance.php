<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeaveBalance extends Model
{
    protected $table='leave_balances';
    public $timestamps=false;
    protected $guarded=['id'];
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Staff::class,'staff_id'); }
    public function leaveType(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(LeaveType::class,'leave_type_id'); }
}