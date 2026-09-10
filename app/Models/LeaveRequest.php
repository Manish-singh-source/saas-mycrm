<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class LeaveRequest extends Model
{
    protected $table='leave_requests';
    protected $guarded=['id'];
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Staff::class,'staff_id'); }
    public function leaveType(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(LeaveType::class,'leave_type_id'); }
    public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(TenantLookup::class,'status_id'); }
    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'approved_by'); }
}