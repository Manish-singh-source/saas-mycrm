<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $guarded = ['id'];
    public function users(): HasMany { return $this->hasMany(User::class, 'staff_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function designation(): BelongsTo { return $this->belongsTo(Designation::class); }
    public function office(): BelongsTo { return $this->belongsTo(TenantOffice::class, 'office_id'); }
    public function primaryTeam(): BelongsTo { return $this->belongsTo(Team::class, 'primary_team_id'); }
    public function reportingManager(): BelongsTo { return $this->belongsTo(User::class, 'reporting_manager_id'); }
    public function bankAccounts(): HasMany { return $this->hasMany(StaffBankAccount::class); }
    public function salaryStructures(): HasMany { return $this->hasMany(StaffSalaryStructure::class); }
}
