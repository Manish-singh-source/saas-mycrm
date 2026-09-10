<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class AttendanceRequest extends Model
{
    protected $table = 'attendance_requests';
    protected $guarded = ['id'];
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}