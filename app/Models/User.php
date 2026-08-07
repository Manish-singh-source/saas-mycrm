<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'client_contact_id',
        'default_office_id',
        'employee_code',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'mobile',
        'password',
        'profile_photo_file_id',
        'timezone',
        'locale',
        'email_verified_at',
        'mobile_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'account_type',
        'last_login_at',
        'last_login_ip',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}