<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';
    protected $guarded = ['id'];
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Staff::class, 'staff_id'); }
    public function status(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(TenantLookup::class, 'status_id'); }
}