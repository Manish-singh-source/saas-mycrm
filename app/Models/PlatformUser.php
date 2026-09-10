<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class PlatformUser extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'platform_users';

    protected $fillable = ['employee_code', 'first_name', 'last_name', 'display_name', 'email', 'mobile', 'profile_photo', 'profile_photo_file_id', 'designation_id', 'department_id', 'manager_id', 'timezone', 'locale'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'designation_id' => 'integer', 'profile_photo_file_id' => 'integer',
        'department_id' => 'integer', 'manager_id' => 'integer',
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_required' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! $user->uuid) $user->uuid = (string) Str::uuid();
            if (! filled($user->employee_code)) {
                $next = self::withTrashed()
                    ->where('employee_code', 'like', 'PL-EMP-%')
                    ->get(['employee_code'])
                    ->map(fn (self $record): int => (int) preg_replace('/^PL-EMP-/', '', (string) $record->employee_code))
                    ->max() + 1;

                do {
                    $code = sprintf('PL-EMP-%04d', $next++);
                } while (self::withTrashed()->where('employee_code', $code)->exists());

                $user->employee_code = $code;
            }
        });
    }

    public function profilePhotoFile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class, 'profile_photo_file_id');
    }

    public function designation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformDesignation::class, 'designation_id');
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PlatformDepartment::class, 'department_id');
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(PlatformRole::class, 'model', 'platform_model_has_roles', 'model_id', 'role_id');
    }

    public function permissions(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(PlatformPermission::class, 'model', 'platform_model_has_permissions', 'model_id', 'permission_id');
    }

    public function teams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PlatformTeam::class, 'platform_team_members', 'platform_user_id', 'platform_team_id')->withPivot(['platform_team_role_id', 'joined_at', 'left_at', 'status']);
    }

    public function platformApiTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformApiToken::class, 'created_by');
    }

    public function idempotencyKeys(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformIdempotencyKey::class, 'platform_user_id');
    }

    public function preferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformUserPreference::class, 'platform_user_id');
    }

    public function createdAnnouncements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformAnnouncement::class, 'created_by');
    }

    public function updatedSettings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformSetting::class, 'updated_by');
    }

    public function assignedTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTicket::class, 'assigned_to');
    }

    public function ticketComments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTicketComment::class, 'platform_user_id');
    }

    public function ticketAttachments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlatformTicketAttachment::class, 'created_by');
    }
}
